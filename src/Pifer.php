<?php  namespace Pif;

class Pifer
{
    public function hash($fname)
    {
        $gd = @imagecreatefromstring(file_get_contents($fname));

        if( !is_resource($gd) ) throw new ExceptionCorruptedImage($fname);
        
        $h1 = intval($this->hashGd($gd, 4));
        $h2 = dechex($this->hashGd($gd, 8));
        
        imagedestroy($gd);
        
        return [$h1, $h2];
    }

    private function hashGd( $gd, $size)
    {
        $width  = $size + 1;
        $height = $size;

        $gd_res = imagecreatetruecolor($width, $height);
        
        imagecopyresampled($gd_res, $gd, 0, 0, 0, 0, $width, $height, imagesx($gd), imagesy($gd));

        $h   = 0;
        $one = 1;
        
        for( $y = 0; $y < $height; $y++ )
        {
            $rgb  = imagecolorsforindex($gd_res, imagecolorat($gd_res, 0, $y));
            $left = floor(($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3);

            for( $x = 1; $x < $width; $x++ )
            {
                $rgb   = imagecolorsforindex($gd_res, imagecolorat($gd_res, $x, $y));
                $right = floor(($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3);

                if( $left > $right ) $h |= $one;

                $left = $right;
                $one  = $one << 1;
            }
        }

        imagedestroy($gd_res);

        return $h;
    }
    
    /**
     * Calculate the Hamming Distance.
     *
     * @param int $hash1
     * @param int $hash2
     * @return int
     */
    public function distance($hash1, $hash2)
    {
        if( extension_loaded('gmp') )
        {
            $dh = gmp_hamdist('0x'.$hash1, '0x'.$hash2);
        }
        else
        {
            $hash1 = $this->hexDec($hash1);
            $hash2 = $this->hexDec($hash2);

            $dh = 0;
            
            for( $i = 0; $i < 64; $i++ )
            {
                $k = (1 << $i);
                
                if( ($hash1 & $k) !== ($hash2 & $k) ) $dh++;
            }
        }

        return $dh;
    }

    public function hexDec( $hex )
    {
        if( strlen($hex) == 16 && hexdec($hex[0]) > 8 )
        {
            list($higher, $lower) = array_values(unpack('N2', hex2bin($hex)));
            return $higher << 32 | $lower;
        }

        return hexdec($hex);
    }

}