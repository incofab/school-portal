<?php
namespace App\Core;

class Helper
{
    public static function copy($source, $target, $cut = false) 
    {
        if (!is_dir($source)) 
        {
            //it is a file, do a normal copy
            copy($source, $target);
            
            if($cut) unlink($source);
            
            return;
        }
        
        //it is a folder, copy its files & sub-folders
        if(!file_exists($target)) @mkdir($target, 0777, true);
        
        $d = dir($source);
        
        $navFolders = array('.', '..');
        
        while (false !== ($fileEntry=$d->read() )) 
        {
            //copy one by one
            
            //skip if it is navigation folder . or ..
            if (in_array($fileEntry, $navFolders) ) 
            {
                continue;
            }
            
            //do copy
            $s = "$source/$fileEntry";
            
            $t = "$target/$fileEntry";
            
            self::copy($s, $t);
        }
        
        $d->close();
    }
    
    /**
     * Helper function to recursively delete a folder and all the files & folders in it
     * @param string $dirPath
     * @throws \InvalidArgumentException
     */
    public static function deleteDir($dirPath, $deleteRootFolder = true)
    {
        if (! is_dir($dirPath))
        {
            throw new \InvalidArgumentException("$dirPath must be a directory");
        }
        
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/')
        {
            $dirPath .= '/';
        }
        
        $files = glob($dirPath . '*', GLOB_MARK);
        
        foreach ($files as $file)
        {
            if (is_dir($file))
            {
                self::deleteDir($file);
            }
            else
            {
                unlink($file);
            }
        }
        
        if($deleteRootFolder) rmdir($dirPath);
    }
    
    public static function zipContent($folderToZip, $zipFilename = 'files.zip') 
    {
        // Get real path for our folder
        $rootPath = realpath($folderToZip);
        
        // Initialize archive object
        $zip = new \ZipArchive();
        
        $zip->open($zipFilename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        // Create recursive directory iterator
        /** @var \SplFileInfo[] $files */
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        
        foreach ($files as $name => $file)
        {
            // Skip directories (they would be added automatically)
            if (!$file->isDir())
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                
                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }
        
        // Zip archive will be created only after closing object
        $zip->close();
    }
    
    public static function is_dir_empty($dir) 
    {
        if(!is_dir($dir)) return true;
    
        return (count(scandir($dir)) == 2);
    }
    
    static function unzip($source, $target)
    {
        if(!file_exists($target)) @mkdir($target, 0777, true);

        if(!file_exists($source)) {
            return [SUCCESSFUL => false, MESSAGE => 'Source file not found'];
        }
        
        try {
            $zip = new \ZipArchive();
            
            $res = $zip->open($source);
            
            if($res !== TRUE) return [SUCCESS => false, MESSAGE => 'File could not open'];
            
            $zip->extractTo($target);
            $zip->close();
            
//             $zipFile = new \PhpZip\ZipFile();;
//             $zipFile->openFile($source) // open archive from file
//             ->extractTo($target); // extract files to the specified directory
            
            static::arrangeFolderFiles($target);
            
            return [SUCCESSFUL => true, MESSAGE => 'Files extracted'];
            
        } catch (\Exception $e) {
            return [SUCCESSFUL => false, MESSAGE => 'Extraction failed: '.$e->getMessage()];        
        }
    }
    
    /**
     * This is important because linux systems does not extract nested files properly
     * @param string $folder
     */
    private static function arrangeFolderFiles($folder) 
    {
        $d = dir($folder);
        
        $navFolders = array('.', '..');
        
        //Analyze one by one
        while (false !== ($fileEntry=$d->read() ))
        {   
            //skip if it is navigation folder . or ..
            if (in_array($fileEntry, $navFolders)) continue;
            
            $arr = explode('\\', $fileEntry);
            $count = count($arr);
            
            if($count === 1) continue;
            
            $baseFolder = $folder;
            for ($i = 0; $i < $count; $i++) 
            {
                if($i == ($count - 1)){
                    $baseFolder = rtrim($baseFolder, '/');
                    
                    $filname = $arr[$i];
                    
                    @mkdir($baseFolder, 0777, true);
                    
                    static::copy("$folder/$fileEntry", "$baseFolder/$filname", true);
                }
                else {
                    $baseFolder .= "/{$arr[$i]}";
                }
            }
        }
    }
    
}









