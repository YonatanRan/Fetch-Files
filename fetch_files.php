<?php
class FetchFiles{

    private $target_folder = '[THE LOCATION WHERE THE FILES WILL BE STORED - ABSOLUTE PATH - Example: /var/www/html/local_files/]';
    private $file_list_filename = '[THE LOCATION OF THE FILE THAT CONTAINS THE REMOTE FILE LIST - Example: /var/www/html/local_files/file_list.txt]';
    //remote server data
    private $remote_folder = '[REMOTE FOLDER THAT FILES WILL BE PULLED FROM - Example: /var/www/html/remote_file_folder/]';

    private $remote_password = "[REMOTE SERVER PASSWORD - Example: password]";
    private $remote_user = '[REMOTE SERVER USERNAME - Example: ubuntu]';
    private $remote_ip = '[REMOTE SERVER IP - Example: 127.0.0.1]';

    public function download_files(){
        $this->get_file_list();
        $this->get_files();
    }

    //first step is getting a file list from the remote server.
    //we get the list by running ls on the remote server folder and storing the result in a txt file.
    //The txt file is saved in the local folder specified above under a folder that looks like: 20151130 - Ymd structure
    //This is to allow this code to run as part of a cronjob and save new files everyday with out overriding previous downloads
    //if you are not interested in this feature you can remove the code: date('Ymd', time()) from the command in the function
    private function get_file_list(){
        $command = "sshpass -p '".$this->remote_password."' ssh -o ConnectTimeout=60 -oStrictHostKeyChecking=no ".$this->remote_user."@".$this->remote_ip." ls ".$this->remote_folder.date('Ymd', time())." > ".$this->file_list_filename;
        exec($command);
    }


    private function get_files(){
        $handle = fopen($this->file_list_filename, "r");
        if ($handle) {
            while (($file = fgets($handle)) !== false) {
                //getting the file
                //checking if the dated sub folder exists, if not, we will create it.
                //if you are not interested in the dated folder structure, you can comment this out.
                if(!is_dir($this->target_folder.date('Ymd', time()))){
                    mkdir($this->target_folder.date('Ymd', time()));
                }
                //informing the user what file is being copied.
                print_r('Copying '.$file);
                //making sure we don't have any line breaks in the file name taken from the txt file.
                $file = str_replace(["\n", "\r"], "", $file);
                //downloading the file
                $command  = "sshpass -p \"".$this->remote_password."\" scp  ".$this->remote_user."@".$this->remote_ip.":".$this->remote_folder.$file." ".$this->target_folder.date('Ymd', time()).'/'.$file;
                //print_r($command);
                exec($command);
            }
            fclose($handle);
        } else {
            print_r('Could not read file.');
        }
    }

}
//step 1 - go to the top of this file and edit the parameters:
//$target_folder
//$file_list_filename
//$remote_folder
//$remote_password
//$remote_user
//$remote_ip
//Step 2 -  get an instance of the class
$get_files = new FetchFiles();
//Step 3 call the download funcion and wait for it to be done
$get_files->download_files();
//To call this from the command line:
//1. Make sure you have PHP installed (DUH!)
//2. run the command as follows:
//php fetch_files.php
?>