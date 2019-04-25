<?php
namespace Parser;

/*
 * Вспомогательные функции
 */
class Helpers {

    /*
     * Проверка файлов по md5
     */
    public static function compareFiles($srcfile, $dstfile) {
        return (hash_file('md5', $srcfile) == hash_file('md5', $dstfile));
    }

    /*
     * Проверка на пустую директорию
     */
    public static function is_dir_empty($dir) {
        if (!is_readable($dir)) return NULL;
        return (count(scandir($dir)) == 2);
    }

    /*
     * Проверяет количество файлов в папке и удаляет старые, есть лимит по количеству файлов
     */
    public static function limitFiles($path, $amount = 10) {

        $amount += 1;
        $result = exec("cd {$path} && ls -1t | tail -n +{$amount} | xargs rm -f");

        return $result;
    }

    /*
     * Имя последнего файла в папке
     */

    public static function lastCreatedFile($path) {
        return $path . exec("cd {$path} && ls -1 -t | head -1");
    }

    /*
     * создание символьной линки по пути
     */
    public static function createSymlink($path, $symlink) {

        if (is_link($path))
            unlink($path);

        if (is_dir($path))
            rmdir($path);

        if (php_sapi_name() === 'cli')
            if (!is_link($symlink))
               exec("ln -s {$symlink} {$path}");

    }

    /*
     * сохранить файл json
     */

    public static function saveJsonFile($filename, $data) {
        $file = fopen($filename, 'w');
        fwrite($file, $data);
        fclose($file);
     /*   chmod($filename,0777);*/
    }

    public static function createDirectories(array $dirArray, $env) {

        foreach ($dirArray as $path) {
            $dir = $env . $path;
            if (!is_dir($dir))
                mkdir($dir, 0777, true);
        }

        return true;
    }

    public static function setMode() {
        return (isset($_GET) && !empty($_GET['mode'])) ? $_GET['mode'] : "f";
    }






}
