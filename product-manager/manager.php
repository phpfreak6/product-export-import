<?php
$ck       = 'ck_4439f14fb6f9c22c9665487a3bd117946b55335b';
$cs       = 'cs_9530c6c944ae5c59e8bb7891c87e4aec4916c330';
$base_url = 'https://demo.balonteam.com/wp-json/wc/v3/products';
$site_url = 'https://demo.balonteam.com';

// Image folder path relative to site root
$image_folder = '/product-manager/images/products/';

$all_sizes = [
    'Youth XX Small (Age 3-4)', 'Youth X Small (Age 5-6)',
    'Youth Small (Age 7-8)',    'Youth Medium (Age 9-10)',
    'Youth Large (Age 11-12)', 'Youth X Large (Age 13-14)',
    'X Small (Age 15-16)', 'Small', 'Medium', 'Large',
    'X Large', 'XX Large', 'XXX Large'
];

$size_slug_map = [
    'Youth XX Small (Age 3-4)'  => 'YXX', 'Youth X Small (Age 5-6)'   => 'YXS',
    'Youth Small (Age 7-8)'     => 'YS',  'Youth Medium (Age 9-10)'   => 'YM',
    'Youth Large (Age 11-12)'   => 'YL',  'Youth X Large (Age 13-14)' => 'YXL',
    'X Small (Age 15-16)'       => 'XS',  'Small'     => 'S',
    'Medium'                    => 'M',   'Large'     => 'L',
    'X Large'                   => 'XL',  'XX Large'  => 'XXL',
    'XXX Large'                 => 'XXXL',
];

// Must match JS normKey() exactly
function normKey($h) {
    return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($h))), '_');
}

function wc_api($url, $method, $data, $ck, $cs) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_USERPWD        => "$ck:$cs",
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $res = curl_exec($ch); curl_close($ch);
    return json_decode($res, true);
}

function wc_get($url, $ck, $cs) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => "$ck:$cs",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $res = curl_exec($ch); curl_close($ch);
    return json_decode($res, true);
}

// ── GET: categories ───────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'get_categories') {
    header('Content-Type: application/json');
    $all = []; $page = 1;
    do {
        $res = wc_get("$base_url/../products/categories?per_page=100&page=$page&orderby=name&order=asc", $ck, $cs);
        if (empty($res) || !is_array($res)) break;
        foreach ($res as $cat)
            if (strtolower($cat['slug']) !== 'uncategorized')
                $all[] = ['id'=>$cat['id'],'name'=>$cat['name'],'count'=>$cat['count']];
        $page++;
    } while (count($res) === 100);
    echo json_encode($all); exit;
}

// ── Helper: build variations from row data ────────────────────────
function build_variations($data, $sku_base, $all_sizes, $size_slug_map) {
    $variations = [];
    foreach ($all_sizes as $size) {
        $price_key = normKey($size);
        $slug      = $size_slug_map[$size];
        $sku_key   = 'sku_' . strtolower($slug);
        $price     = trim($data[$price_key] ?? '');
        if ($price === '' || !is_numeric($price) || floatval($price) <= 0) continue;
        $custom_sku = trim($data[$sku_key] ?? '');
        $variations[] = [
            'size'  => $size,
            'price' => number_format(floatval($price), 2, '.', ''),
            'sku'   => !empty($custom_sku) ? $custom_sku : ($sku_base . '-' . $slug),
        ];
    }
    return $variations;
}

// ── Helper: apply initials addon ──────────────────────────────────
function apply_initials($pid, $data, $base_url, $ck, $cs) {
    $enable = strtolower(trim($data['initials_enable'] ?? 'no'));
    if ($enable !== 'yes') return '';
    $title = trim($data['initials_title'] ?? 'WOULD YOU LIKE INITIALS?');
    $desc  = trim($data['initials_description'] ?? '');
    $fee   = floatval($data['initials_fee'] ?? 0);
    $res   = wc_api("$base_url/$pid", 'PUT', [
        'meta_data' => [['key' => '_product_addons', 'value' => [[
            'name'               => $title,
            'title_format'       => 'label',
            'description_enable' => 1,
            'description'        => $desc,
            'type'               => 'custom_text',
            'position'           => 0, 'required' => 0,
            'restrictions'       => 0, 'restrictions_type' => 'any_text',
            'adjust_price'       => $fee > 0 ? 1 : 0,
            'price_type'         => 'flat_fee',
            'price'              => (string)$fee,
            'min' => 0, 'max' => 0, 'options' => [],
        ]]]]
    ], $ck, $cs);
    return !empty($res['id']) ? ' ✏️ +initials' : ' ⚠️ initials check needed';
}

// ── POST: CREATE product ──────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'import_row') {
    header('Content-Type: application/json');
    set_time_limit(60);
    global $all_sizes, $size_slug_map, $ck, $cs, $base_url, $site_url, $image_folder;

    $rows    = json_decode($_POST['rows'],       true);
    $headers = json_decode($_POST['headers'],    true);
    $idx     = intval($_POST['row_index']);
    $cats    = json_decode($_POST['categories'], true);

    $row  = $rows[$idx];
    $data = array_combine($headers, $row);

    $name_val = trim($data['product_name'] ?? '');
    $sku_base = trim($data['sku_base']     ?? '');
    if (empty($name_val)) { echo json_encode(['status'=>'skipped','msg'=>'Empty row']); exit; }
    if (empty($sku_base)) { echo json_encode(['status'=>'skipped','msg'=>"$name_val: empty SKU"]); exit; }

    $img_col = trim($data['image_url'] ?? '');
    $img_url = '';
    if (!empty($img_col))
        $img_url = filter_var($img_col, FILTER_VALIDATE_URL)
            ? $img_col
            : rtrim($site_url,'/').'/'.trim($image_folder,'/').'/'.ltrim($img_col,'/');

    $variations = build_variations($data, $sku_base, $all_sizes, $size_slug_map);
    if (empty($variations)) {
        echo json_encode(['status'=>'failed','msg'=>"$name_val: no size prices found"]); exit;
    }

    // ── Check if SKU already exists — if so, UPDATE instead of create ──
    $existing = wc_get("$base_url?sku=$sku_base&per_page=1", $ck, $cs);
    if (!empty($existing[0]['id'])) {
        $pid = $existing[0]['id'];

        // Update product details
        $update_payload = [
            'name'        => $name_val,
            'description' => trim($data['description'] ?? ''),
            'attributes'  => [['name'=>'Size','visible'=>true,'variation'=>true,
                               'options'=>array_column($variations,'size')]],
        ];
        if (!empty($img_url)) $update_payload['images'] = [['src'=>$img_url]];
        wc_api("$base_url/$pid", 'PUT', $update_payload, $ck, $cs);

        // Get existing variations and batch update/create/delete
        $existing_vars = wc_get("$base_url/$pid/variations?per_page=100", $ck, $cs);
        $existing_map  = [];
        foreach ((array)$existing_vars as $ev) {
            foreach ((array)$ev['attributes'] as $a) {
                if (strtolower($a['name']) === 'size') { $existing_map[$a['option']] = $ev['id']; break; }
            }
        }
        $batch = ['create'=>[], 'update'=>[], 'delete'=>[]];
        foreach ($variations as $v) {
            $vd = ['sku'=>$v['sku'],'regular_price'=>$v['price'],'stock_status'=>'instock',
                   'attributes'=>[['name'=>'Size','option'=>$v['size']]]];
            if (isset($existing_map[$v['size']])) { $vd['id']=$existing_map[$v['size']]; $batch['update'][]=$vd; }
            else $batch['create'][]=$vd;
        }
        $new_sizes = array_column($variations,'size');
        foreach ($existing_map as $size=>$vid) {
            if (!in_array($size,$new_sizes)) $batch['delete'][]=$vid;
        }
        $br = wc_api("$base_url/$pid/variations/batch", 'POST', $batch, $ck, $cs);
        $v_ok = count($br['update']??[]) + count($br['create']??[]);
        $addon_msg = apply_initials($pid, $data, $base_url, $ck, $cs);

        echo json_encode([
            'status'  => 'updated',
            'msg'     => "$name_val (SKU existed → auto-updated) — $v_ok/".count($variations)." variations$addon_msg",
            'edit_url'=> "https://demo.balonteam.com/wp-admin/post.php?post=$pid&action=edit",
        ]); exit;
    }

    // ── SKU is new — create fresh ──
    $cat_ids = array_map(fn($c) => ['id'=>intval($c['id'])], $cats);
    $payload = [
        'name'        => $name_val, 'type' => 'variable',
        'sku'         => $sku_base,
        'description' => trim($data['description'] ?? ''),
        'categories'  => $cat_ids,
        'attributes'  => [['name'=>'Size','visible'=>true,'variation'=>true,
                           'options'=>array_column($variations,'size')]],
    ];
    if (!empty($img_url)) $payload['images'] = [['src'=>$img_url]];

    $product = wc_api($base_url, 'POST', $payload, $ck, $cs);
    if (empty($product['id'])) {
        echo json_encode(['status'=>'failed','msg'=>"$name_val: ".($product['message']??json_encode($product))]); exit;
    }

    $pid = $product['id'];

    $batch = ['create' => array_map(fn($v) => [
        'sku'           => $v['sku'],
        'regular_price' => $v['price'],
        'stock_status'  => 'instock',
        'attributes'    => [['name'=>'Size','option'=>$v['size']]],
    ], $variations)];
    $batch_res = wc_api("$base_url/$pid/variations/batch", 'POST', $batch, $ck, $cs);
    $v_ok = count($batch_res['create'] ?? []);

    $addon_msg = apply_initials($pid, $data, $base_url, $ck, $cs);

    echo json_encode([
        'status'  => 'created',
        'msg'     => "$name_val — $v_ok/".count($variations)." variations$addon_msg",
        'edit_url'=> "https://demo.balonteam.com/wp-admin/post.php?post=$pid&action=edit",
    ]); exit;
}

// ── POST: UPDATE product ──────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'update_row') {
    header('Content-Type: application/json');
    set_time_limit(60);
    global $all_sizes, $size_slug_map, $ck, $cs, $base_url, $site_url, $image_folder;

    $rows    = json_decode($_POST['rows'],    true);
    $headers = json_decode($_POST['headers'], true);
    $idx     = intval($_POST['row_index']);

    $row  = $rows[$idx];
    $data = array_combine($headers, $row);

    $name_val  = trim($data['product_name'] ?? '');
    $sku_base  = trim($data['sku_base']     ?? '');
    $product_id= intval($data['product_id'] ?? 0);

    if (empty($name_val)) { echo json_encode(['status'=>'skipped','msg'=>'Empty row']); exit; }
    if (empty($sku_base)) { echo json_encode(['status'=>'skipped','msg'=>"$name_val: empty SKU"]); exit; }

    // Find product by product_id (from export) or by SKU
    $pid = 0;
    if ($product_id > 0) {
        $pid = $product_id;
    } else {
        // Search by SKU
        $found = wc_get("$base_url?sku=$sku_base&per_page=1", $ck, $cs);
        if (!empty($found[0]['id'])) $pid = $found[0]['id'];
    }

    if (!$pid) {
        echo json_encode(['status'=>'skipped','msg'=>"$name_val: product not found (SKU: $sku_base)"]); exit;
    }

    $variations = build_variations($data, $sku_base, $all_sizes, $size_slug_map);
    if (empty($variations)) {
        echo json_encode(['status'=>'failed','msg'=>"$name_val: no size prices found"]); exit;
    }

    // Update product name, description, image
    $img_col = trim($data['image_url'] ?? '');
    $img_url = '';
    if (!empty($img_col))
        $img_url = filter_var($img_col, FILTER_VALIDATE_URL)
            ? $img_col
            : rtrim($site_url,'/').'/'.trim($image_folder,'/').'/'.ltrim($img_col,'/');

    $update_payload = [
        'name'        => $name_val,
        'description' => trim($data['description'] ?? ''),
        'attributes'  => [['name'=>'Size','visible'=>true,'variation'=>true,
                           'options'=>array_column($variations,'size')]],
    ];
    if (!empty($img_url)) $update_payload['images'] = [['src'=>$img_url]];

    wc_api("$base_url/$pid", 'PUT', $update_payload, $ck, $cs);

    // Get existing variations
    $existing_vars = wc_get("$base_url/$pid/variations?per_page=100", $ck, $cs);
    $existing_map  = [];
    foreach ((array)$existing_vars as $ev) {
        $size_attr = null;
        foreach ((array)$ev['attributes'] as $a) {
            if (strtolower($a['name']) === 'size') { $size_attr = $a['option']; break; }
        }
        if ($size_attr) $existing_map[$size_attr] = $ev['id'];
    }

    // Build batch: update existing, create new
    $batch = ['create' => [], 'update' => []];
    foreach ($variations as $v) {
        $var_data = [
            'sku'           => $v['sku'],
            'regular_price' => $v['price'],
            'stock_status'  => 'instock',
            'attributes'    => [['name'=>'Size','option'=>$v['size']]],
        ];
        if (isset($existing_map[$v['size']])) {
            $var_data['id'] = $existing_map[$v['size']];
            $batch['update'][] = $var_data;
        } else {
            $batch['create'][] = $var_data;
        }
    }

    // Delete variations not in new sheet
    $new_sizes = array_column($variations, 'size');
    $batch['delete'] = [];
    foreach ($existing_map as $size => $var_id) {
        if (!in_array($size, $new_sizes)) $batch['delete'][] = $var_id;
    }

    $batch_res = wc_api("$base_url/$pid/variations/batch", 'POST', $batch, $ck, $cs);
    $v_updated = count($batch_res['update'] ?? []);
    $v_created = count($batch_res['create'] ?? []);
    $v_deleted = count($batch_res['delete'] ?? []);

    $addon_msg = apply_initials($pid, $data, $base_url, $ck, $cs);

    $detail = [];
    if ($v_updated) $detail[] = "$v_updated updated";
    if ($v_created) $detail[] = "$v_created added";
    if ($v_deleted) $detail[] = "$v_deleted removed";

    echo json_encode([
        'status'  => 'updated',
        'msg'     => "$name_val — variations: ".implode(', ',$detail)."$addon_msg",
        'edit_url'=> "https://demo.balonteam.com/wp-admin/post.php?post=$pid&action=edit",
    ]); exit;
}

// ── POST: EXPORT products ─────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'export_products') {
    header('Content-Type: application/json');
    set_time_limit(120);
    global $ck, $cs, $base_url;

    $cat_ids = json_decode($_POST['cat_ids'], true);
    $limit   = $_POST['limit'];
    $per_page = ($limit === 'all' || intval($limit) > 100) ? 100 : intval($limit);

    $url = "$base_url?per_page=$per_page&status=publish";
    if (!empty($cat_ids)) $url .= '&category=' . implode(',', $cat_ids);

    $products = wc_get($url, $ck, $cs);
    if (!is_array($products)) { echo json_encode(['products'=>[]]); exit; }

    // Fetch variations for each product
    foreach ($products as &$p) {
        if ($p['type'] === 'variable') {
            $vars = wc_get("$base_url/{$p['id']}/variations?per_page=100", $ck, $cs);
            $p['variations_detail'] = is_array($vars) ? $vars : [];
        } else {
            $p['variations_detail'] = [];
        }
    }
    unset($p);

    echo json_encode(['products' => $products]); exit;
}