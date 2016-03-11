<?php
/**
 * GIF Image creator
 *
 * @version 1.0.0
 * @link http://www.24beta.com/
 * @author Dong Chen (cdcchen@gmail.com)
 * @copyright Dong Chen (cdcchen@gmail.com)
 * @license BSD License
 */

namespace phpplus\graphics;

/**
 * Class GIFCreator
 * @package yiiext\graphics
 */
Class GIFCreator
{
    const VERSION = '1.0.0';
    const FRAMES_MODE_FILE = 'file';
    const FRAMES_MODE_BINARY = 'bin';

    protected $_gif = 'GIF89a'; // GIF header 6 bytes

    protected $buffer = [];
    protected $loop =  0;
    protected $disposal =  2;
    protected $color = 0;
    protected $_img = -1;

    protected $errors = [
		'ERR00' => 'Does not supported function for only one image!',
		'ERR01' => 'Source is not a GIF image!',
		'ERR02' => 'Unintelligible flag ',
		'ERR03' => 'Does not make animation from animated GIF source',
    ];

    private $_images = [];
    private $_delays = [];
    private $_mode = self::FRAMES_MODE_FILE;

    /**
     * @return string
     */
    public function getGIF()
    {
        return $this->_gif;
    }

    public function __toString()
    {
        return $this->_gif;
    }


	public function __construct(array $images, array $delays, $mode = self::FRAMES_MODE_FILE)
    {
        $this->setImages($images, $delays, $mode)
            ->buildImagesBuffer();
	}

    public function create()
    {
        static::addHeader();

        for ($i = 0; $i < count($this->buffer); $i++)
            static::addFrame($i, $this->_delays[$i]);

        static::addFooter();
    }

    /**
     * @param array $images image frames array
     * @param array $delays image delay array
     * @param string $mode images frames data model, file or bin
     * @return $this
     * @throws \Exception
     */
    public function setImages(array $images, array $delays, $mode = self::FRAMES_MODE_FILE)
    {
        if (!is_array($images) && !is_array($delays))
            throw new \Exception(self::VERSION . ': ' . $this->errors['ERR00']);

        $this->_images = $images;
        $this->_delays = $delays;
        $this->_mode = $mode;

        return $this;
    }


    public function setLoop($n)
    {
        $this->loop = ($n > 0) ? $n : 0;
        return $this->loop;
    }

    public function setDisposal($n)
    {
        $this->disposal = ($n > -1) ? (($n < 3) ? $n : 3) : 2;
        return $this;
    }

    public function setColor($red, $green, $blue)
    {
        $this->color = ($red > -1 && $green > -1 && $blue > -1) ? ($red | ($green << 8) | ($blue << 16)) : -1;
        return $this;
    }


    protected function buildImagesBuffer()
    {
        for ($i = 0; $i < count($this->_images); $i++) {
            if (strtolower($this->_mode) === self::FRAMES_MODE_FILE)
                $this->buffer[] = file_get_contents($this->_images[$i]);
            elseif (strtolower($this->_mode) === self::FRAMES_MODE_BINARY)
                $this->buffer[] = $this->_images[$i];
            else
                throw new \Exception(sprintf('%s: %s (%s)!', self::VERSION, $this->errors['ERR02'], $this->_mode));

            if (substr($this->buffer[$i], 0, 6) !== 'GIF87a' && substr($this->buffer[$i], 0, 6) !== 'GIF89a')
                throw new \Exception(sprintf('%s: %d %s', self::VERSION, $i, $this->errors['ERR01']));

            for ($j = (13 + 3 * (2 << (ord($this->buffer[$i][10]) & 0x07))), $k = true; $k; $j++) {
                switch ($this->buffer[$i][$j]) {
                    case '!':
                        if ((substr($this->buffer[$i], ($j + 3), 8)) === 'NETSCAPE')
                            throw new \Exception(sprintf('%s: %s (%s source)!', self::VERSION, $this->errors['ERR03'], ($i + 1)));
                        break;
                    case ';':
                        $k = false;
                        break;
                    default:
                        break;
                }
            }
        }

        return $this;
    }


    protected function addHeader()
    {
		if (ord($this->buffer[0][10]) & 0x80) {
			$cMap = 3 * (2 << (ord($this->buffer[0][10]) & 0x07));

			$this->_gif .= substr($this->buffer[0], 6, 7);
			$this->_gif .= substr($this->buffer[0], 13, $cMap);
			$this->_gif .= "!\377\13NETSCAPE2.0\3\1" . static::getGIFWord($this->loop) . "\0";
		}

		return $this;
	}

    /**
     * @param int $i
     * @param int $delay
     */
    protected function addFrame($i, $delay)
    {
		$localsStr = 13 + 3 * (2 << (ord($this->buffer[$i][10]) & 0x07));

		$localsEnd = strlen($this->buffer[$i]) - $localsStr - 1;
		$localsTmp = substr($this->buffer[$i], $localsStr, $localsEnd);

		$globalLen = 2 << (ord($this->buffer[0 ][10]) & 0x07);
		$localsLen = 2 << (ord($this->buffer[$i][10]) & 0x07);

		$globalRGB = substr($this->buffer[0 ], 13,
							3 * (2 << (ord($this->buffer[0 ][10]) & 0x07)));
		$localsRGB = substr($this->buffer[$i], 13,
							3 * (2 << (ord($this->buffer[$i][10]) & 0x07)));

		$localsExt = "!\xF9\x04" . chr(($this->disposal << 2) + 0) .
						chr(($delay >> 0) & 0xFF) . chr(($delay >> 8) & 0xFF) . "\x0\x0";

		if ($this->color > -1 && ord($this->buffer[$i][10]) & 0x80) {
			for ($j = 0; $j < (2 << (ord($this->buffer[$i][10]) & 0x07)); $j++) {
				if	(
						ord($localsRGB[3 * $j + 0]) == (($this->color >> 16) & 0xFF) &&
						ord($localsRGB[3 * $j + 1]) == (($this->color >>  8) & 0xFF) &&
						ord($localsRGB[3 * $j + 2]) == (($this->color >>  0) & 0xFF)
					)
                {
					$localsExt = "!\xF9\x04" . chr(($this->disposal << 2) + 1) .
									chr(($delay >> 0) & 0xFF) . chr(($delay >> 8) & 0xFF) . chr($j) . "\0";
					break;
				}
			}
		}

        $localsImg = '';
		switch ($localsTmp[0]) {
			case '!':
				$localsImg = substr($localsTmp, 8, 10);
				$localsTmp = substr($localsTmp, 18, strlen($localsTmp) - 18);
				break;
			case ',':
				$localsImg = substr($localsTmp, 0, 10);
				$localsTmp = substr($localsTmp, 10, strlen($localsTmp) - 10);
				break;
		}

		if (ord($this->buffer[$i][10]) & 0x80 && $this->_img > -1) {
			if ($globalLen == $localsLen) {
				if (static::blockCompare($globalRGB, $localsRGB, $globalLen)) {
					$this->_gif .= ($localsExt . $localsImg . $localsTmp);
				}
				else {
					$byte  = ord($localsImg[9]);
					$byte |= 0x80;
					$byte &= 0xF8;
					$byte |= (ord($this->buffer[0][10]) & 0x07);
					$localsImg[9] = chr($byte);
					$this->_gif .= ($localsExt . $localsImg . $localsRGB . $localsTmp);
				}
			}
			else {
				$byte  = ord($localsImg[9]);
				$byte |= 0x80;
				$byte &= 0xF8;
				$byte |= (ord($this->buffer[$i][10]) & 0x07);
				$localsImg[9] = chr($byte);
				$this->_gif .= ($localsExt . $localsImg . $localsRGB . $localsTmp);
			}
		}
		else {
			$this->_gif .= ($localsExt . $localsImg . $localsTmp);
		}
		$this->_img  = 1;
	}


    protected function addFooter()
    {
		$this->_gif .= ';';
	}

    /**
     * @param array $globalBlock
     * @param array $localBlock
     * @param int $len
     * @return bool
     */
    protected function blockCompare ($globalBlock, $localBlock, $len)
    {
		for ($i = 0; $i < $len; $i++) {
			if ($globalBlock[3 * $i + 0] != $localBlock[3 * $i + 0] ||
				$globalBlock[3 * $i + 1] != $localBlock[3 * $i + 1] ||
				$globalBlock[3 * $i + 2] != $localBlock[3 * $i + 2])
            {
                return false;
			}
		}

		return true;
	}

    /**
     * @param $int
     * @return string
     */
	protected function getGIFWord($int)
    {
		return (chr($int & 0xFF) . chr(($int >> 8) & 0xFF));
	}
}