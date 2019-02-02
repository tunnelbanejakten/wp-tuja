<?php

namespace tuja\util;

use DateTime;
use SimpleXMLElement;
use XMLReader;

class Message
{
    public $from = null;
    public $date = null;
    public $texts = array();
    public $images = array();
}

class SmsBackupRestoreXmlFileProcessor
{
    private $image_folder;
    private $date_limit;

    public function __construct($image_folder, $date_limit)
    {
        $this->image_folder = $image_folder;
        $this->date_limit = $date_limit;
    }

    public function process($xml_file)
    {
        $res = array();
        $reader = new XMLReader();
        $reader->open($xml_file);
        while ($reader->read()) {
            if ($reader->name == 'mms') {
                $mms_element = new SimpleXMLElement($reader->readOuterXml());

                $message_date = new DateTime("@" . substr($mms_element['date'], 0, 10));

                if (isset($this->date_limit) && $message_date < $this->date_limit) {
                    continue;
                }
                // TODO: Group individual messages sent, say, less than 30 seconds apart in case groups send the image as one message and the description as another.

                $image_parts = $mms_element->xpath("parts/part[@ct='image/jpeg']");
                $is_image_included = !empty($image_parts);

                if ($is_image_included) {
                    $message = new Message();
                    $text_parts = $mms_element->xpath("parts/part[@ct='text/plain']");
                    $message->from = Phone::fix_phone_number($mms_element['address']);
                    $message->date = $message_date;
                    foreach ($text_parts as $text_part) {
                        $message->texts[] = $text_part['text'];
                    }
                    foreach ($image_parts as $image_part) {
//                        $filename = $image_part['name'];
                        $path = SmsBackupRestoreXmlFileProcessor::create_temp_file();
                        file_put_contents($path, base64_decode($image_part['data']));
//                        printf("%s, %d bytes\n", $path, filesize($path));
                        $message->images[] = $path;
                    }
                    $res[] = $message;
                }
                unset($mms_element);
            }
        }
        return $res;
    }

    /**
     * Create a temporary file which will be delete by PHP itself once the script completes.
     *
     * @return absolute path to the newly created file, e.g: /tmp/phpFx0513a.
     */
    private static function create_temp_file()
    {
        return stream_get_meta_data(tmpfile())['uri'];
    }
}

//(new SmsBackupRestoreXmlFileProcessor('.'))->process('../../messages.xml');