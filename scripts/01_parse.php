<?php
$rawPath = dirname(__DIR__) . '/raw';
if (!file_exists($rawPath)) {
    mkdir($rawPath, 0777, true);
}

$fc = [
    'type' => 'FeatureCollection',
    'features' => [],
];
for ($i = 1; $i <= 7; $i++) {
    $pageFile = $rawPath . '/page_' . $i . '.html';
    if (!file_exists($pageFile)) {
        file_put_contents($pageFile, file_get_contents('https://tnma.tainan.gov.tw/MarketList.aspx?Pindex=' . $i));
    }
    $page = file_get_contents($pageFile);
    $posEnd = 0;
    $pos = strpos($page, '<td>名稱：', $posEnd);
    while (false !== $pos) {
        $posEnd = strpos($page, '</td>', $pos);
        $part = substr($page, $pos, $posEnd - $pos);
        $part1 = explode('Market.aspx?Cond=', $part);
        $part2 = explode('"', $part1[1]);
        $itemRaw = $rawPath . '/' . $part2[0] . '.html';
        if (!file_exists($itemRaw)) {
            file_put_contents($itemRaw, file_get_contents('https://tnma.tainan.gov.tw/Market.aspx?Cond=' . $part2[0]));
        }
        $raw = file_get_contents($itemRaw);
        $rawPos = strpos($raw, '<div class="news_content">');
        $rawPos = strpos($raw, '<span>', $rawPos);
        $rawPosEnd = strpos($raw, '</th>', $rawPos);
        $parts = explode('/', preg_replace('/\s+/', '', strip_tags(substr($raw, $rawPos, $rawPosEnd - $rawPos))));
        $data = [
            'url' => 'https://tnma.tainan.gov.tw/Market.aspx?Cond=' . $part2[0],
            'name' => $parts[0],
            'area' => str_replace('　', '', $parts[1]),
            'scope' => $parts[2],
        ];
        $rawPos = strpos($raw, '<li class="ap01">', $rawPos);
        $rawPosEnd = strpos($raw, '</li>', $rawPos);
        $parts = explode('：', preg_replace('/\s+/', '', strip_tags(substr($raw, $rawPos, $rawPosEnd - $rawPos))));
        $data['address'] = $parts[1];
        $rawPos = strpos($raw, '<li class="ap02">', $rawPos);
        $rawPosEnd = strpos($raw, '</li>', $rawPos);
        $data['phone'] = preg_replace('/\s+/', '', strip_tags(substr($raw, $rawPos, $rawPosEnd - $rawPos)));
        $rawPos = strpos($raw, '<li class="ap03">', $rawPos);
        $rawPosEnd = strpos($raw, '</li>', $rawPos);
        $parts = explode('：', preg_replace('/\s+/', '', strip_tags(substr($raw, $rawPos, $rawPosEnd - $rawPos))));
        $data['time'] = $parts[1];
        $rawPos = strpos($raw, '/FileDownLoad/Market/Big/', $rawPos);
        $rawPosEnd = strpos($raw, '"', $rawPos);
        $data['img'] = 'https://tnma.tainan.gov.tw' . substr($raw, $rawPos, $rawPosEnd - $rawPos);
        $rawPos = strpos($raw, '市集故事', $rawPos);
        $rawPos = strpos($raw, '</p>', $rawPos);
        $rawPosEnd = strpos($raw, '</div>', $rawPos);
        $lines = explode('<br />', substr($raw, $rawPos, $rawPosEnd - $rawPos));
        foreach ($lines as $k => $v) {
            $lines[$k] = trim(strip_tags($v));
        }
        $data['story'] = implode("\n", $lines);
        $rawPos = strpos($raw, '<span class="fe2">', $rawPos);
        $rawPosEnd = strpos($raw, '</span>', $rawPos);
        $data['built'] = trim(strip_tags(substr($raw, $rawPos, $rawPosEnd - $rawPos)));
        $rawPos = strpos($raw, '<p>', $rawPos);
        $rawPosEnd = strpos($raw, '</p>', $rawPos);
        $lines = explode('<br />', substr($raw, $rawPos, $rawPosEnd - $rawPos));
        foreach ($lines as $k => $v) {
            $lines[$k] = trim(strip_tags($v));
        }
        $data['background'] = implode("\n", $lines);
        $rawPos = strpos($raw, '<p style="width:100%;" >', $rawPos);
        $rawPosEnd = strpos($raw, '</p>', $rawPos);
        $lines = explode('<br />', substr($raw, $rawPos, $rawPosEnd - $rawPos));
        foreach ($lines as $k => $v) {
            $lines[$k] = trim(strip_tags($v));
        }
        $data['feature'] = implode("\n", $lines);
        $rawPos = strpos($raw, '<span class="fe2">', $rawPos);
        $rawPosEnd = strpos($raw, '</span>', $rawPos);
        $data['count_stores'] = intval(trim(strip_tags(substr($raw, $rawPos, $rawPosEnd - $rawPos))));
        $rawPos = strpos($raw, '<span class="fe4">', $rawPos);
        $rawPosEnd = strpos($raw, '</span>', $rawPos);
        $data['category'] = trim(strip_tags(substr($raw, $rawPos, $rawPosEnd - $rawPos)));
        $rawPos = strpos($raw, '<p style="width:100%;">', $rawPos);
        $rawPosEnd = strpos($raw, '</p>', $rawPos);
        $lines = explode('</a>', substr($raw, $rawPos, $rawPosEnd - $rawPos));
        $data['stores'] = [];
        foreach ($lines as $k => $v) {
            $part1 = explode('Store.aspx?Cond=', $v);
            if (!isset($part1[1])) {
                continue;
            }
            $part2 = explode('"', $part1[1]);
            $store = [
                'url' => 'https://tnma.tainan.gov.tw/Store.aspx?Cond=' . $part2[0],
            ];
            $part1 = explode('【攤號：', strip_tags($v));
            $part2 = explode('】', $part1[1]);
            $store['no'] = $part2[0];
            $store['name'] = trim($part2[1]);
            $data['stores'][] = $store;
        }
        $rawPos = strpos($raw, 'ctl00_ContentPlaceHolder1_hfCurrentLat', $rawPos);
        $rawPosEnd = strpos($raw, '</div>', $rawPos);
        $parts = explode('"', substr($raw, $rawPos, $rawPosEnd - $rawPos));
        $data['latitude'] = isset($parts[2]) ? floatval($parts[2]) : 0.0;
        $data['longitude'] = isset($parts[10]) ? floatval($parts[10]) : 0.0;
        if($data['latitude'] > $data['longitude']) {
            $tmp = $data['latitude'];
            $data['latitude'] = $data['longitude'];
            $data['longitude'] = $tmp;
        }
        $dataPath = dirname(__DIR__) . '/docs/data/' . $data['area'];
        if (!file_exists($dataPath)) {
            mkdir($dataPath, 0777, true);
        }

        $fc['features'][] = [
            'type' => 'Feature',
            'properties' => [
                'area' => $data['area'],
                'name' => $data['name'],
            ],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    $data['longitude'],
                    $data['latitude']
                ],
            ],
        ];

        file_put_contents($dataPath . '/' . $data['name'] . '.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $pos = strpos($page, '<td>名稱：', $posEnd);
    }
}

file_put_contents(dirname(__DIR__) . '/docs/points.json', json_encode($fc, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
