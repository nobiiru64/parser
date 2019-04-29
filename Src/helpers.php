<?php
if (!function_exists('compareFiles')) {
    /**
     * Проверка файлов по md5
     * @return true|false
     */
    function compareFiles($srcfile, $dstfile)
    {
        return (hash_file('md5', $srcfile) == hash_file('md5', $dstfile));
    }
}

if (!function_exists('is_dir_empty')) {
    /**
    * Проверка на пустую директорию
    * @return true|false
    */
    function is_dir_empty($dir) {
        if (!is_readable($dir)) return NULL;
        return (count(scandir($dir)) == 2);
    }
}

if (!function_exists('limitFiles')) {
    /**
    * Проверяет количество файлов в папке и удаляет старые, есть лимит по количеству файлов
    *  @return string
    */
    function limitFiles($path, $amount = 10) {

        if (!is_dir_empty($path)){
            $amount += 1;
            $result = exec("cd {$path} && ls -1t | tail -n +{$amount} | xargs rm -f");
            if ($result)
                return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('lastCreatedFile')) {
    /**
    * Имя последнего файла в папке
    */
    function lastCreatedFile($path) {
        return $path . exec("cd {$path} && ls -1 -t | head -1");
    }
}

if (!function_exists('createSymLink')) {
    /**
    * создание символьной линки по пути
    */
    function createSymlink($path, $symlink) {

        if (is_link($path))
            unlink($path);

        if (is_dir($path))
            rmdir($path);

        if (php_sapi_name() === 'cli')
            if (!is_link($symlink))
                exec("ln -s {$symlink} {$path}");

        return true;
    }
}

if (!function_exists('createDirectories')) {
    function createDirectories(array $dirArray, $env) {
        $dirCreated = true;
        foreach ($dirArray as $path) {
            $dir = $env . $path;
            if (!is_dir($dir))
                if (!mkdir($dir, 0777, true))
                    $dirCreated = false;
        }
        return $dirCreated;
    }

}

if (!function_exists('saveJsonFile')) {
    /*
    * сохранить файл json
    */
    function saveJsonFile($filename, $data) {

        $file = fopen($filename, 'w');
        if ($file)
            if (fwrite($file, $data))
                if (fclose($file))
                    return true;
        /*   chmod($filename,0777);*/
        return false;
    }
}



if (!function_exists('setMode')) {

    function setMode() {
        return (isset($_GET) && !empty($_GET['mode'])) ? $_GET['mode'] : "f";
    }
}

if (!function_exists('createUrl')){
    function createUrl($korp, $sec, $floor, $fnumb, $numb, $qty, $id){

        $link = "/apartments/korp{$korp}/sec{$sec}/floor{$floor}/fnumb{$fnumb}/numb{$numb}/quantity{$qty}/id{$id}/";

        return $link;
    }
}

if (!function_exists('delete_directory')) {

    function delete_directory($dirname) {
        if (is_dir($dirname))
            $dir_handle = opendir($dirname);
        if (!$dir_handle)
            return false;
        while($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file))
                    unlink($dirname."/".$file);
                else
                    delete_directory($dirname.'/'.$file);
            }
        }
        closedir($dir_handle);
        rmdir($dirname);
        return true;
    }
}
