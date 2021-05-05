<?php

define('RAW_EXTENSION', '.RW2');
define('RAW_SETTING_DIR', 'SILKYPIX_DS');

// 第1引数は処理対象のディレクトリを指定。
$target_path = $argv[1] ?? null;
if (empty($target_path) || ! file_exists($target_path) || ! is_dir($target_path)) {
    echo("ERROR: target directory is not found.\r\n");
    exit(1);
}

// 第2引数は移動先のディレクトリを指定。
$send_path = $argv[2] ?? null;
if (empty($send_path) || ! file_exists($send_path) || ! is_dir($send_path)) {
    echo("ERROR: send directory is not found.\r\n");
    exit(1);
}

// 第3引数にexecuteを渡された場合だけ実際の処理を実行する
$second_arg = $argv[3] ?? null;
$is_dry_run = true;
if ($second_arg == 'execute') {
    $is_dry_run = false;
}

// ファイルパス指定のフォーマットを調整しておく
if (mb_strrpos($target_path, DIRECTORY_SEPARATOR) != (mb_strlen($target_path) -1)) {
    $target_path .= DIRECTORY_SEPARATOR;
}
if (mb_strrpos($send_path, DIRECTORY_SEPARATOR) != (mb_strlen($send_path) -1)) {
    $send_path .= DIRECTORY_SEPARATOR;
}

// 先頭にドットを含まない拡張子指定が行われている場合エラー終了
if (mb_strpos(RAW_EXTENSION, '.') !== 0) {
    echo("ERROR: invalid extension.\r\n");
    exit(1);
}

// RAW現像設定ディレクトリが見つからない場合は警告終了にしておく
$target_raw_dir = $target_path . RAW_SETTING_DIR . DIRECTORY_SEPARATOR;
if (! file_exists($target_raw_dir) || ! is_dir($target_raw_dir)) {
    echo("WARNING: raw setting dir not found.\r\n");
    echo("process exited.");
    exit(2);
}

// 対象RAWファイルの走査
$extensions_search = '{*' . RAW_EXTENSION . '}';
$target_raw_list = glob($target_path . $extensions_search, GLOB_BRACE);
echo 'target files count ' . count($target_raw_list). "\r\n";

foreach ($target_raw_list as $raw_file_path) {
    // echo "TARGET: {$raw_file_path}\r\n";

    $raw_file_name = mb_substr($raw_file_path, (mb_strrpos($raw_file_path, DIRECTORY_SEPARATOR) +1));

    // RAWだけが有りJPEG画像が無い場合はRAWは削除対象外。スキップ。
    $file_name_no_extension = mb_substr($raw_file_name, 0, mb_strrpos($raw_file_name, RAW_EXTENSION));
    // echo "{$file_name_no_extension}\r\n";
    if (empty(glob($target_path . $file_name_no_extension . '{.jpg,.JPG,.jpeg,.JPEG,.png,.PNG}', GLOB_BRACE))) {
        echo "skip because {$file_name_no_extension}.jpg|.JPG|.jpeg|.JPEG|.png|.PNG cannot be found.\r\n";
        continue;
    }

    // TODO: RAW現像設定ファイルが有るかを確認。
    if (! empty(glob($target_raw_dir . $raw_file_name . '*'))) {
        echo 'is printed： ' . $raw_file_name . "\r\n";
    } else {
        echo 'not printed： ' . $raw_file_name . "\r\n";
        
        $moved_path = $send_path . $raw_file_name;
        if (file_exists($moved_path)) {
            echo "WARNING: file move failed. exists: " . $moved_path . "\r\n";
            continue;
        }
        if (! $is_dry_run) {
            rename($raw_file_path, $moved_path);
            echo "EXECUTE: file moved: " . $moved_path . "\r\n";
        }
    }
}
