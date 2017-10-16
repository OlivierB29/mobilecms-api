<?php

class Autoloader
{
    private static $classNames = [];

    /**
     * Store the filename (sans extension) & full path of all ".php" files found.
     */
    public static function registerDirectory($dirName)
    {
        $classes = 'classes';

        $di = new DirectoryIterator($dirName);
        foreach ($di as $file) {
            if ($file->isDir() && !$file->isLink() && !$file->isDot()) {
                // recurse into directories other than a few special ones
                self::registerDirectory($file->getPathname());
            } elseif (substr($file->getFilename(), -4) === '.php') {
                // save the class name / path of a .php file found
                $className = substr($file->getFilename(), 0, -4);

                // eg: project/classes/foo/bar
                $namespaceDir = dirname($file->getPathname());
                // eg: foo/bar
                $namespace = substr($namespaceDir, strpos($namespaceDir, $classes) + strlen($classes) + 1);

                // eg: \foo\bar\
                $namespace = str_replace('/', '\\', $namespace) . '\\';

                // eg: \foo\bar\MyClass
                $namespaceClassName = $namespace . $className;

                self::registerClass($namespaceClassName, $file->getPathname());
            }
        }
    }

    public static function registerClass($className, $fileName)
    {
        self::$classNames[$className] = $fileName;
    }

    public static function loadClass($className)
    {
        if (isset(self::$classNames[$className])) {
            require_once self::$classNames[$className];
        }
    }
}
