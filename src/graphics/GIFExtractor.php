<?php

/**
 * Extract the frames (and their duration) of a GIF
 * 
 * @version 1.0.0
 * @link http://www.24beta.com/ (http://www.24beta.com/)
 * @author Dong Chen (cdcchen@gmail.com)
 * @copyright Dong Chen (cdcchen@gmail.com)
 * @license BSD License
 */

namespace phpplus\graphics;

/**
 * Class GIFExtractor
 * @package yiiext\graphics
 */
class GIFExtractor
{
    /**
     * @var resource
     */
    private $_gif;
    
    /**
     * @var array
     */
    private $_frames;
    
    /**
     * @var array
     */
    private $_frameDurations;
    
    /**
     * @var array
     */
    private $_frameImages;
    
    /**
     * @var array
     */
    private $_framePositions;
    
    /**
     * @var array
     */
    private $_frameDimensions;
    
    /**
     * @var integer 
     * 
     */
    private $_frameNumber;
    
    /**
     * @var array 
     * 
     */
    private $_frameSources;
    
    /**
     * @var array 
     */
    private $_fileHeader;
    
    /**
     * @var integer The reader pointer in the file source 
     */
    private $_pointer;
    
    /**
     * @var integer
     */
    private $_gifMaxWidth;
    
    /**
     * @var integer
     */
    private $_gifMaxHeight;
    
    /**
     * @var integer
     */
    private $totalDuration;
    
    /**
     * @var integer|resource
     */
    private $_handler;
    
    /**
     * @var array 
     */
    private $_globalData;
    
    /**
     * @var array 
     */
    private $_orgVars;
    

    /**
     * Extract frames of a GIF
     *
     * @param string $filename $filename GIF filename path
     * @param bool $createImage create image resource
     * @param bool $originalFrames Get original frames (with transparent background)
     * @return array
     * @throws \Exception
     */
    public function extract($filename, $createImage = false, $originalFrames = false)
    {
        if (!self::isAnimatedGif($filename)) {
            throw new \Exception('The GIF image you are trying to explode is not animated !');
        }
        
        $this->reset();
        $this->parseFramesInfo($filename);
        $prevImg = null;
        
        for ($i = 0; $i < count($this->_frameSources); $i++) {
            
            $this->_frames[$i] = [];
            $this->_frameDurations[$i] = $this->_frames[$i]['duration'] = $this->_frameSources[$i]['delay_time'];

            $img = $this->_fileHeader['gifheader'] . $this->_frameSources[$i]['graphicsextension'] . $this->_frameSources[$i]['imagedata'] . chr(0x3b);
            if ($createImage) {
                $img = imagecreatefromstring($img);
                if (!$originalFrames) {
                    if ($i > 0)
                        $prevImg = $this->_frames[$i - 1]['image'];
                    else
                        $prevImg = $img;

                    $sprite = imagecreate($this->_gifMaxWidth, $this->_gifMaxHeight);
                    imagesavealpha($sprite, true);

                    $transparent = imagecolortransparent($prevImg);

                    if ($transparent > -1 && imagecolorstotal($prevImg) > $transparent) {

                        $actualTrans = imagecolorsforindex($prevImg, $transparent);
                        imagecolortransparent($sprite, imagecolorallocate($sprite, $actualTrans['red'], $actualTrans['green'], $actualTrans['blue']));
                    }

                    if ((int)$this->_frameSources[$i]['disposal_method'] == 1 && $i > 0) {

                        imagecopy($sprite, $prevImg, 0, 0, 0, 0, $this->_gifMaxWidth, $this->_gifMaxHeight);
                    }

                    imagecopyresampled($sprite, $img, $this->_frameSources[$i]['offset_left'], $this->_frameSources[$i]['offset_top'], 0, 0, $this->_gifMaxWidth, $this->_gifMaxHeight, $this->_gifMaxWidth, $this->_gifMaxHeight);
                    $img = $sprite;
                }
            }
            
            $this->_frameImages[$i] = $this->_frames[$i]['image'] = $img;
        }
        
        return $this->_frames;
    }
    
    /**
     * Check if a GIF file at a path is animated or not
     * 
     * @param string $filename GIF path
     * @return bool
     */
    public static function isAnimatedGif($filename)
    {
        if (!($fh = @fopen($filename, 'rb')))
            return false;

        $count = 0;

        while (!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); //read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
        }
        
        fclose($fh);
        return $count > 1;
    }
    
    /**
     * Parse the frame informations contained in the GIF file
     * 
     * @param string $filename GIF filename path
     */
    private function parseFramesInfo($filename)
    {
        $this->openFile($filename);
        $this->parseGifHeader();
        $this->parseGraphicsExtension(0);
        $this->getApplicationData();
        $this->getApplicationData();
        $this->getFrameString(0);
        $this->parseGraphicsExtension(1);
        $this->getCommentData();
        $this->getApplicationData();
        $this->getFrameString(1);
        
        while (!$this->checkByte(0x3b) && !$this->checkEOF()) {
            
            $this->getCommentData();
            $this->parseGraphicsExtension(2);
            $this->getFrameString(2);
            $this->getApplicationData();
        }
        $this->closeFile();
    }
    
    /** 
     * Parse the gif header (old: get_gif_header)
     */
    private function parseGifHeader()
    {
        $this->pointerForward(10);
        
        if ($this->readBits(($mybyte = $this->readByteInt()), 0, 1) == 1) {
            
            $this->pointerForward(2);
            $this->pointerForward(pow(2, $this->readBits($mybyte, 5, 3) + 1) * 3);
            
        } else {
            
            $this->pointerForward(2);
        }

        $this->_fileHeader['gifheader'] = $this->dataPart(0, $this->_pointer);
        
        // Decoding
        $this->_orgVars['gifheader'] = $this->_fileHeader['gifheader'];
        $this->_orgVars['background_color'] = $this->_orgVars['gifheader'][11];
    }
    
    /**
     * Parse the application data of the frames (old: get_application_data)
     */
    private function getApplicationData()
    {
        $startData = $this->readByte(2);
        
        if ($startData == chr(0x21).chr(0xff)) {
            
            $start = $this->_pointer - 2;
            $this->pointerForward($this->readByteInt());
            $this->readDataStream($this->readByteInt());
            $this->_fileHeader['applicationdata'] = $this->dataPart($start, $this->_pointer - $start);
            
        } else {
            
            $this->pointerRewind(2);
        }
    }
    
    /**
     * Parse the comment data of the frames (old: get_comment_data)
     */
    private function getCommentData()
    {
        $startData = $this->readByte(2);
        
        if ($startData == chr(0x21).chr(0xfe)) {
            
            $start = $this->_pointer - 2;
            $this->readDataStream($this->readByteInt());
            $this->_fileHeader['commentdata'] = $this->dataPart($start, $this->_pointer - $start);
            
        } else {
            
            $this->pointerRewind(2);
        }
    }
    
    /**
     * Parse the graphic extension of the frames (old: get_graphics_extension)
     * 
     * @param integer $type
     */
    private function parseGraphicsExtension($type)
    {
        $startData = $this->readByte(2);
        
        if ($startData == chr(0x21).chr(0xf9)) {
            
            $start = $this->_pointer - 2;
            $this->pointerForward($this->readByteInt());
            $this->pointerForward(1);
            
            if ($type == 2) {
                
                $this->_frameSources[$this->_frameNumber]['graphicsextension'] = $this->dataPart($start, $this->_pointer - $start);
                
            } elseif ($type == 1) {
                
                $this->_orgVars['hasgx_type_1'] = 1;
                $this->_globalData['graphicsextension'] = $this->dataPart($start, $this->_pointer - $start);

            } elseif ($type == 0) {

                $this->_orgVars['hasgx_type_0'] = 1;
                $this->_globalData['graphicsextension_0'] = $this->dataPart($start, $this->_pointer - $start);
            }

        } else {

            $this->pointerRewind(2);
        }
    }

    /**
     * Get the full frame string block (old: get_image_block)
     *
     * @param integer $type
     */
    private function getFrameString($type)
    {
        if ($this->checkByte(0x2c)) {

            $start = $this->_pointer;
            $this->pointerForward(9);

            if ($this->readBits(($mybyte = $this->readByteInt()), 0, 1) == 1) {

                $this->pointerForward(pow(2, $this->readBits($mybyte, 5, 3) + 1) * 3);
            }

            $this->pointerForward(1);
            $this->readDataStream($this->readByteInt());
            $this->_frameSources[$this->_frameNumber]['imagedata'] = $this->dataPart($start, $this->_pointer - $start);

            if ($type == 0) {

                $this->_orgVars['hasgx_type_0'] = 0;

                if (isset($this->_globalData['graphicsextension_0'])) {

                    $this->_frameSources[$this->_frameNumber]['graphicsextension'] = $this->_globalData['graphicsextension_0'];

                } else {

                    $this->_frameSources[$this->_frameNumber]['graphicsextension'] = null;
                }

                unset($this->_globalData['graphicsextension_0']);

            } elseif ($type == 1) {

                if (isset($this->_orgVars['hasgx_type_1']) && $this->_orgVars['hasgx_type_1'] == 1) {

                    $this->_orgVars['hasgx_type_1'] = 0;
                    $this->_frameSources[$this->_frameNumber]['graphicsextension'] = $this->_globalData['graphicsextension'];
                    unset($this->_globalData['graphicsextension']);

                } else {

                    $this->_orgVars['hasgx_type_0'] = 0;
                    $this->_frameSources[$this->_frameNumber]['graphicsextension'] = $this->_globalData['graphicsextension_0'];
                    unset($this->_globalData['graphicsextension_0']);
                }
            }

            $this->parseFrameData();
            $this->_frameNumber++;
        }
    }

    /**
     * Parse frame data string into an array (old: parse_image_data)
     */
    private function parseFrameData()
    {
        $this->_frameSources[$this->_frameNumber]['disposal_method'] = $this->getImageDataBit('ext', 3, 3, 3);
        $this->_frameSources[$this->_frameNumber]['user_input_flag'] = $this->getImageDataBit('ext', 3, 6, 1);
        $this->_frameSources[$this->_frameNumber]['transparent_color_flag'] = $this->getImageDataBit('ext', 3, 7, 1);
        $this->_frameSources[$this->_frameNumber]['delay_time'] = $this->dualByteVal($this->getImageDataByte('ext', 4, 2));
        $this->totalDuration += (int) $this->_frameSources[$this->_frameNumber]['delay_time'];
        $this->_frameSources[$this->_frameNumber]['transparent_color_index'] = ord($this->getImageDataByte('ext', 6, 1));
        $this->_frameSources[$this->_frameNumber]['offset_left'] = $this->dualByteVal($this->getImageDataByte('dat', 1, 2));
        $this->_frameSources[$this->_frameNumber]['offset_top'] = $this->dualByteVal($this->getImageDataByte('dat', 3, 2));
        $this->_frameSources[$this->_frameNumber]['width'] = $this->dualByteVal($this->getImageDataByte('dat', 5, 2));
        $this->_frameSources[$this->_frameNumber]['height'] = $this->dualByteVal($this->getImageDataByte('dat', 7, 2));
        $this->_frameSources[$this->_frameNumber]['local_color_table_flag'] = $this->getImageDataBit('dat', 9, 0, 1);
        $this->_frameSources[$this->_frameNumber]['interlace_flag'] = $this->getImageDataBit('dat', 9, 1, 1);
        $this->_frameSources[$this->_frameNumber]['sort_flag'] = $this->getImageDataBit('dat', 9, 2, 1);
        $this->_frameSources[$this->_frameNumber]['color_table_size'] = pow(2, $this->getImageDataBit('dat', 9, 5, 3) + 1) * 3;
        $this->_frameSources[$this->_frameNumber]['color_table'] = substr($this->_frameSources[$this->_frameNumber]['imagedata'], 10, $this->_frameSources[$this->_frameNumber]['color_table_size']);
        $this->_frameSources[$this->_frameNumber]['lzw_code_size'] = ord($this->getImageDataByte('dat', 10, 1));

        $this->_framePositions[$this->_frameNumber] = [
            'x' => $this->_frameSources[$this->_frameNumber]['offset_left'],
            'y' => $this->_frameSources[$this->_frameNumber]['offset_top'],
        ];

        $this->_frameDimensions[$this->_frameNumber] = [
            'width' => $this->_frameSources[$this->_frameNumber]['width'],
            'height' => $this->_frameSources[$this->_frameNumber]['height'],
        ];

        // Decoding
        $this->_orgVars[$this->_frameNumber]['transparent_color_flag'] = $this->_frameSources[$this->_frameNumber]['transparent_color_flag'];
        $this->_orgVars[$this->_frameNumber]['transparent_color_index'] = $this->_frameSources[$this->_frameNumber]['transparent_color_index'];
        $this->_orgVars[$this->_frameNumber]['delay_time'] = $this->_frameSources[$this->_frameNumber]['delay_time'];
        $this->_orgVars[$this->_frameNumber]['disposal_method'] = $this->_frameSources[$this->_frameNumber]['disposal_method'];
        $this->_orgVars[$this->_frameNumber]['offset_left'] = $this->_frameSources[$this->_frameNumber]['offset_left'];
        $this->_orgVars[$this->_frameNumber]['offset_top'] = $this->_frameSources[$this->_frameNumber]['offset_top'];

        // Updating the max width
        if ($this->_gifMaxWidth < $this->_frameSources[$this->_frameNumber]['width']) {

            $this->_gifMaxWidth = $this->_frameSources[$this->_frameNumber]['width'];
        }

        // Updating the max height
        if ($this->_gifMaxHeight < $this->_frameSources[$this->_frameNumber]['height']) {

            $this->_gifMaxHeight = $this->_frameSources[$this->_frameNumber]['height'];
        }
    }

    /**
     * Get the image data byte (old: get_imagedata_byte)
     *
     * @param string $type
     * @param integer $start
     * @param integer $length
     *
     * @return string
     */
    private function getImageDataByte($type, $start, $length)
    {
        if ($type == 'ext') {

            return substr($this->_frameSources[$this->_frameNumber]['graphicsextension'], $start, $length);
        }

        // 'dat'
        return substr($this->_frameSources[$this->_frameNumber]['imagedata'], $start, $length);
    }

    /**
     * Get the image data bit (old: get_imagedata_bit)
     *
     * @param string $type
     * @param integer $byteIndex
     * @param integer $bitStart
     * @param integer $bitLength
     *
     * @return number
     */
    private function getImageDataBit($type, $byteIndex, $bitStart, $bitLength)
    {
        if ($type == 'ext') {

            return $this->readBits(ord(substr($this->_frameSources[$this->_frameNumber]['graphicsextension'], $byteIndex, 1)), $bitStart, $bitLength);
        }

        // 'dat'
        return $this->readBits(ord(substr($this->_frameSources[$this->_frameNumber]['imagedata'], $byteIndex, 1)), $bitStart, $bitLength);
    }

    /**
     * Return the value of 2 ASCII chars (old: dualbyteval)
     *
     * @param string $s
     *
     * @return integer
     */
    private function dualByteVal($s)
    {
        $i = ord($s[1]) * 256 + ord($s[0]);

        return $i;
    }

    /**
     * Read the data stream (old: read_data_stream)
     *
     * @param integer $firstLength
     */
    private function readDataStream($firstLength)
    {
        $this->pointerForward($firstLength);
        $length = $this->readByteInt();

        if ($length != 0) {

            while ($length != 0) {

                $this->pointerForward($length);
                $length = $this->readByteInt();
            }
        }
    }

    /**
     * Open the gif file
     *
     * @param string $filename
     */
    private function openFile($filename)
    {
        $this->_handler = fopen($filename, 'rb');
        $this->_pointer = 0;

        $imageSize = getimagesize($filename);
        $this->_gifMaxWidth = $imageSize[0];
        $this->_gifMaxHeight = $imageSize[1];
    }

    /**
     * Close the read gif file (old: closefile)
     */
    private function closeFile()
    {
        fclose($this->_handler);
        $this->_handler = 0;
    }

    /**
     * Read the file from the beginning to $byteCount in binary (old: readbyte)
     *
     * @param integer $byteCount
     *
     * @return string
     */
    private function readByte($byteCount)
    {
        $data = fread($this->_handler, $byteCount);
        $this->_pointer += $byteCount;

        return $data;
    }

    /**
     * Read a byte and return ASCII value (old: readbyte_int)
     *
     * @return integer
     */
    private function readByteInt()
    {
        $data = fread($this->_handler, 1);
        $this->_pointer++;

        return ord($data);
    }

    /**
     * Convert a $byte to decimal (old: readbits)
     *
     * @param string $byte
     * @param integer $start
     * @param integer $length
     *
     * @return number
     */
    private function readBits($byte, $start, $length)
    {
        $bin = str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        $data = substr($bin, $start, $length);

        return bindec($data);
    }

    /**
     * Rewind the file pointer reader (old: p_rewind)
     *
     * @param integer $length
     */
    private function pointerRewind($length)
    {
        $this->_pointer -= $length;
        fseek($this->_handler, $this->_pointer);
    }

    /**
     * Forward the file pointer reader (old: p_forward)
     *
     * @param integer $length
     */
    private function pointerForward($length)
    {
        $this->_pointer += $length;
        fseek($this->_handler, $this->_pointer);
    }

    /**
     * Get a section of the data from $start to $start + $length (old: datapart)
     *
     * @param integer $start
     * @param integer $length
     *
     * @return string
     */
    private function dataPart($start, $length)
    {
        fseek($this->_handler, $start);
        $data = fread($this->_handler, $length);
        fseek($this->_handler, $this->_pointer);

        return $data;
    }

    /**
     * Check if a character if a byte (old: checkbyte)
     *
     * @param integer $byte
     *
     * @return boolean
     */
    private function checkByte($byte)
    {
        if (fgetc($this->_handler) == chr($byte)) {

            fseek($this->_handler, $this->_pointer);
            return true;
        }

        fseek($this->_handler, $this->_pointer);

        return false;
    }

    /**
     * Check the end of the file (old: checkEOF)
     *
     * @return boolean
     */
    private function checkEOF()
    {
        if (fgetc($this->_handler) === false) {

            return true;
        }

        fseek($this->_handler, $this->_pointer);

        return false;
    }

    /**
     * Reset and clear this current object
     */
    private function reset()
    {
        $this->_gif = null;
        $this->totalDuration = $this->_gifMaxHeight = $this->_gifMaxWidth = $this->_handler = $this->_pointer = $this->_frameNumber = 0;
        $this->_frameDimensions = $this->_framePositions = $this->_frameImages = $this->_frameDurations = $this->_globalData = $this->_orgVars = $this->_frames = $this->_fileHeader = $this->_frameSources = [];
    }
    
    // Getter / Setter
    // ===================================================================================
    
    /**
     * Get the total of all added frame duration
     * 
     * @return integer
     */
    public function getTotalDuration()
    {
        return $this->totalDuration;
    }
    
    /**
     * Get the number of extracted frames
     * 
     * @return integer
     */
    public function getFrameNumber()
    {
        return $this->_frameNumber;
    }
    
    /**
     * Get the extracted frames (images and durations)
     * 
     * @return array
     */
    public function getFrames()
    {
        return $this->_frames;
    }
    
    /**
     * Get the extracted frame positions
     * 
     * @return array
     */
    public function getFramePositions()
    {
        return $this->_framePositions;
    }
    
    /**
     * Get the extracted frame dimensions
     * 
     * @return array
     */
    public function getFrameDimensions()
    {
        return $this->_frameDimensions;
    }
    
    /**
     * Get the extracted frame images
     * 
     * @return array
     */
    public function getFrameImages()
    {
        return $this->_frameImages;
    }
    
    /**
     * Get the extracted frame durations
     * 
     * @return array
     */
    public function getFrameDurations()
    {
        return $this->_frameDurations;
    }
}