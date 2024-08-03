<?php

function flog( $str, $timestamp = false ) {

	$file = 'log.log';

	$date = date( 'Ymd-G:i:s' ); // 20171231-23:59:59
	$date = $date . '-' . microtime( true );
	$file = __DIR__ . DIRECTORY_SEPARATOR . $file;

	$str = print_r( $str, true );
	file_put_contents( $file, $str . PHP_EOL, FILE_APPEND | LOCK_EX );
}

// Function to calculate the greatest common divisor
function gcd( $a, $b ) {
	return $b ? gcd( $b, $a % $b ) : $a;
}

// Function to calculate and return the closest aspect ratio value (1-8)
function getAspectRatio( $width, $height ) {
	// Calculate the GCD and reduce the dimensions
	$gcd           = gcd( $width, $height );
	$reducedWidth  = $width / $gcd;
	$reducedHeight = $height / $gcd;
	$aspectRatio   = $reducedWidth / $reducedHeight; // Calculate the decimal aspect ratio

	// Define known aspect ratios and their corresponding values
	$aspectRatios = array(
		'4:3'   => array(
			'ratio' => 1.3333,
			'value' => 1,
		),
		'14:9'  => array(
			'ratio' => 1.5556,
			'value' => 2,
		),
		'16:9'  => array(
			'ratio' => 1.7778,
			'value' => 3,
		),
		'5:4'   => array(
			'ratio' => 1.25,
			'value' => 4,
		),
		'16:10' => array(
			'ratio' => 1.6,
			'value' => 5,
		),
		'15:9'  => array(
			'ratio' => 1.6667,
			'value' => 6,
		),
		'21:9'  => array(
			'ratio' => 2.3333,
			'value' => 7,
		),
		'64:27' => array(
			'ratio' => 2.3704,
			'value' => 8,
		),
	);

	// Initialize variables to find the closest aspect ratio
	$closestValue      = null;
	$minimumDifference = PHP_FLOAT_MAX;

	// Iterate through each aspect ratio to find the closest one
	foreach ( $aspectRatios as $key => $info ) {
		// Calculate the absolute difference between the current ratio and the computed ratio
		$difference = abs( $info['ratio'] - $aspectRatio );

		// Check if this difference is the smallest we have encountered
		// print_r( 'Difference: ' . $difference . ' Minimum Difference' . $minimumDifference . PHP_EOL );
		if ( $difference < $minimumDifference ) {
			$minimumDifference = $difference;
			$closestValue      = $info['value'];
		}
	}

	// Return the value associated with the closest aspect ratio
	// print_r( 'Aspect Ratio: ' . $closestValue . PHP_EOL );
	return $closestValue;
}


function generateModeline( $smodeline = '' ) {
	if ( empty( $_POST['modeline'] ) ) {
		return;
	}
	$smodeline = $_POST['modeline'];
	$pattern   = '/Modeline\s+".*"\s+(\d+\.\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\S+)\s+(\S+)/';
	preg_match( $pattern, $smodeline, $matches );
	flog( $smodeline );
	$modeline = array(
		'pixelClock'    => 0,
		'hactive'       => 0,
		'hsyncStart'    => 0,
		'hsyncEnd'      => 0,
		'htotal'        => 0,
		'vactive'       => 0,
		'vsyncStart'    => 0,
		'vsyncEnd'      => 0,
		'vtotal'        => 0,
		'hsyncPolarity' => 0,
		'vsyncPolarity' => 0,
		'refreshRate'   => 0,
	);
	// Extract values from regex matches
	$modeline['pixelClock']    = floatval( $matches[1] ) * 1000000; // Hz
	$modeline['hactive']       = intval( $matches[2] );
	$modeline['hsyncStart']    = intval( $matches[3] );
	$modeline['hsyncEnd']      = intval( $matches[4] );
	$modeline['htotal']        = intval( $matches[5] );
	$modeline['vactive']       = intval( $matches[6] );
	$modeline['vsyncStart']    = intval( $matches[7] );
	$modeline['vsyncEnd']      = intval( $matches[8] );
	$modeline['vtotal']        = intval( $matches[9] );
	$modeline['hsyncPolarity'] = $matches[10] == '+hsync' ? +1 : -1;
	$modeline['vsyncPolarity'] = $matches[11] == '+vsync' ? +1 : -1;

	preg_match( '/([\d.]+)Hz/', $smodeline, $r1 );
	preg_match( '/([\d.]+)Hz/', $smodeline, $r2 );
	// $modeline['refreshRate'] = intval( $matches[1] );
	$r1 = intval( $r1[1] );
	$r2 = intval( $r2[1] );
	if ( $r1 === $r2 ) {
		$modeline['refreshRate'] = $r1;
	}

	// Calculate derived values
	$hfp   = $modeline['hsyncStart'] - $modeline['hactive'];
	$hsync = $modeline['hsyncEnd'] - $modeline['hsyncStart'];
	$hbp   = $modeline['htotal'] - $modeline['hsyncEnd'];
	$vfp   = $modeline['vsyncStart'] - $modeline['vactive'];
	$vsync = $modeline['vsyncEnd'] - $modeline['vsyncStart'];
	$vbp   = $modeline['vtotal'] - $modeline['vsyncEnd'];

	$hFreq               = $modeline['pixelClock'] / $modeline['htotal']; // in Hz
	$vFreq               = $hFreq / $modeline['vtotal']; // in Hz
	$horizontal_blanking = max( 160, ceil( $modeline['hactive'] * 0.2 / 8 ) * 8 );
	$vertical_blanking   = max( 3, ceil( $vFreq / 60 ) ); // Example calculation, adjust logic as needed

	$cvt_modeline = array(
		'hactive'     => $modeline['hactive'],
		'hblank'      => $horizontal_blanking,
		'vactive'     => $modeline['vactive'],
		'vblank'      => $vertical_blanking,
		'refreshRate' => $vFreq,
	);
	// print_r( 'Calculated CVT Modeline' );
	// print_r( $cvt_modeline );
	// return;

	$dtparam = array(
		'clock-frequency' => $modeline['pixelClock'], // Pixel Clock (Hz)
		'hactive'         => $modeline['hactive'], // Horizontal Active
		'hfp'             => $hfp, // Horizontal Front Porch
		'hsync'           => $hsync, // Horizontal Sync
		'hbp'             => $hbp, // Horizontal Back Porch
		'vactive'         => $modeline['vactive'], // Vertical Active
		'vfp'             => $vfp, // Vertical Front Porch
		'vsync'           => $vsync, // Vertical Sync
		'vbp'             => $vbp, // Vertical Back Porch
		'frame_rate'      => $modeline['refreshRate'], // 'Refresh Rate'
	);

	if ( $modeline['hsyncPolarity'] < 0 ) {
		$dtparam['hsync-invert'] = 'hsync-invert';
	} else {
		$dtparam['hsync-invert'] = 'hsync-noinvert';
	}
	if ( $modeline['vsyncPolarity'] < 0 ) {
		$dtparam['vsync-invert'] = 'vsync-invert';
	} else {
		$dtparam['vsync-invert'] = 'vsync-noinvert';
	}

	print_r( '# Calculated dtparam values for KMS:' . PHP_EOL );
	print_r(
		'dtoverlay=vc4-kms-dpi-generic,rgb666,'
		. 'clock-frequency=' . $dtparam['clock-frequency'] . PHP_EOL
		. 'dtparam=hactive=' . $dtparam['hactive'] . ','
		. $dtparam['hsync-invert'] . ','
		. 'hfp=' . $dtparam['hfp'] . ','
		. 'hsync=' . $dtparam['hsync'] . ','
		. 'hbp=' . $dtparam['hbp'] . PHP_EOL
		. 'dtparam=vactive=' . $dtparam['vactive'] . ','
		. $dtparam['vsync-invert'] . ','
		. 'vfp=' . $dtparam['vfp'] . ','
		. 'vsync=' . $dtparam['vsync'] . ','
		. 'vbp=' . $dtparam['vbp'] . PHP_EOL
		// . 'frame_rate=' . $dtparam['frame_rate'] . ','
	);

	$dpi_timings = array(
		'hactive'    => $dtparam['hactive'],
		'hfp'        => $dtparam['hfp'],
		'hsync'      => $dtparam['hsync'],
		'hbp'        => $dtparam['hbp'],
		'vactive'    => $modeline['vactive'],
		'vfp'        => $dtparam['vactive'],
		'vsync'      => $dtparam['vsync'],
		'vbp'        => $dtparam['vbp'],
		'frame_rate' => $modeline['refreshRate'],
	);

	if ( $modeline['hsyncPolarity'] < 0 ) {
		$dpi_timings['hsync-polarity'] = 1;
	} else {
		$dpi_timings['hsync-polarity'] = 0;
	}
	if ( $modeline['vsyncPolarity'] < 0 ) {
		$dpi_timings['vsync-polarity'] = 1;
	} else {
		$dpi_timings['vsync-polarity'] = 0;
	}

	$dpi_timings['aspect_ratio'] = getAspectRatio( $dpi_timings['hactive'], $dpi_timings['vactive'] );

	print_r( PHP_EOL . '# Calculated dpi_timings values for FKMS:' . PHP_EOL );
	// hdmi_timings=<h_active_pixels> <h_sync_polarity> <h_front_porch> <h_sync_pulse> <h_back_porch> <v_active_lines> <v_sync_polarity> <v_front_porch> <v_sync_pulse> <v_back_porch> <v_sync_offset_a> <v_sync_offset_b> <pixel_rep> <frame_rate> <interlaced> <pixel_freq> <aspect_ratio>
	print_r(
		'dtoverlay=vc4-fkms-v3d' . PHP_EOL .
		'dtoverlay=vga666' . PHP_EOL .
		'enable_dpi_lcd=1' . PHP_EOL .
		'display_default_lcd=1' . PHP_EOL .
		'dpi_group=2' . PHP_EOL .
		'dpi_mode=87' . PHP_EOL .
		'dpi_timings='
		. $dpi_timings['hactive'] . ' ' . $dpi_timings['hsync-polarity'] . ' ' . $dpi_timings['hfp'] . ' ' . $dpi_timings['hsync'] . ' ' . $dpi_timings['hbp'] . ' '
		. $dpi_timings['vactive'] . ' ' . $dpi_timings['vsync-polarity'] . ' ' . $dpi_timings['vfp'] . ' ' . $dpi_timings['vsync'] . ' ' . $dpi_timings['vbp'] . ' '
		. '0 0 0 ' . $dpi_timings['frame_rate'] . ' 0 ' . $modeline['pixelClock'] . ' ' . $dpi_timings['aspect_ratio']
	);
}

// Example Modeline string
$modeline = 'Modeline "256x240_50 15.600000KHz 50.000000Hz" 4.898400 256 263 286 314 240 267 269 312   -hsync -vsync'; // PAL
// Process the Modeline
// generateModeline( $modeline );
// echo PHP_EOL . 'PAL' . PHP_EOL;

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title></title>
	<!-- <link href="style.css" rel="stylesheet" /><script> -->
	</script>
</head>
<body>
	
	<p>This program helps you generate code for Raspberry Pi config.txt for 240p gaming. See: https://forums.libretro.com/t/switchres-basic-minimum-requirements/45472/22</p>
	<p>Modeline can be generated with switchres for a specific display, eg.: switchres -v -c 640 480 50 -m pal</p>
	<form action="<?php echo htmlspecialchars( $_SERVER['PHP_SELF'] ); ?>" method="post">
	<p><label for="modeline">Enter a valid Modeline:</label></p>
	
	<p><input type="text" required minlength="1" maxlength="255" size="100" name="modeline" id="modeline" length  placeholder="Must start with 'Modeline'" value="<?php echo isset( $_POST['modeline'] ) ? htmlspecialchars( $_POST['modeline'] ) : ''; ?>" /></p>
	<p><button type="submit">Generate</button></p>
	<?php
	if ( isset( $_POST['modeline'] ) ) {
		?>
		
		<p>You can copy and paste the following data into your config.txt.</p>
		<textarea name="config" id="config" cols="80" rows="15" placeholder="config.txt"><?php generateModeline(); ?></textarea> 
		<?php
	}
	?>
</body>
</html>