<?php

function generateModeline( $smodeline ) {
	$pattern = '/Modeline\s+".*"\s+(\d+\.\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\S+)\s+(\S+)/';
	preg_match( $pattern, $smodeline, $matches );

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
	// $modeline['pixelClock']    = floatval( $matches[1] ) * 1000000; // Hz
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
    

	print_r( 'Original Modeline' );
	print_r( $modeline );

	// Calculate derived values
	$hfp   = $modeline['hsyncStart'] - $modeline['hactive'];
	$hsync = $modeline['hsyncEnd'] - $modeline['hsyncStart'];
	$hbp   = $modeline['htotal'] - $modeline['hsyncEnd'];
	$vfp   = $modeline['vsyncStart'] - $modeline['vactive'];
	$vsync = $modeline['vsyncEnd'] - $modeline['vsyncStart'];
	$vbp   = $modeline['vtotal'] - $modeline['vsyncEnd'];

    $hFreq = $modeline['pixelClock'] / $modeline['htotal']; // in Hz
    $vFreq = $hFreq / $modeline['vtotal']; // in Hz
    $cvt_modeline = array(
        'hactive' => $modeline['hactive'],
        'hblank'  => $horizontal_blanking,
        'vactive' => $modeline['vactive'],
        'vblank'  => $vertical_blanking,
        'refreshRate' => $vFreq
    );
    print_r( 'Calculated CVT Modeline' );
    print_r( $cvt_modeline );
    return;

	$dtparm = array(
		// 'clock-frequency' => $modeline['htotal'] * $modeline['vtotal'] * $modeline['refreshRate'], // Pixel Clock (Hz)
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
		$dtparm['hsync-invert'] = 'hsync-invert';
	} else {
		$dtparm['hsync-invert'] = 'hsync-noinvert';
	}
	if ( $modeline['vsyncPolarity'] < 0 ) {
		$dtparm['vsync-invert'] = 'vsync-invert';
	} else {
		$dtparm['vsync-invert'] = 'vsync-noinvert';
	}

	print_r( 'Calculated dtparm values:' . PHP_EOL );
	print_r(
		'dtoverlay=vc4-kms-dpi-generic,'
		. 'hactive=' . $dtparm['hactive'] . ','
		. 'hfp=' . $dtparm['hfp'] . ','
		. 'hsync=' . $dtparm['hsync'] . ','
		. 'hbp=' . $dtparm['hbp'] . PHP_EOL
		. 'dtparm=vactive=' . $dtparm['vactive'] . ','
		. 'vfp=' . $dtparm['vfp'] . ','
		. 'vsync=' . $dtparm['vsync'] . ','
		. 'vbp=' . $dtparm['vbp'] . ',' . PHP_EOL
		. 'dtparm=clock-frequency=' . $dtparm['clock-frequency'] . ','
		// . 'frame_rate=' . $dtparm['frame_rate'] . ','
		. $dtparm['hsync-invert'] . ','
		. $dtparm['vsync-invert'] . ',rgb666' .

		PHP_EOL
	);
}

// Example Modeline string
$modeline = 'Modeline "352x288_50 15.650000KHz 50.000000Hz" 7.152050 352 366 400 457 288 292 295 313 -hsync -vsync';
$modeline = 'Modeline "360x288_50 15.650000KHz 50.000000Hz" 7.308550 360 375 409 467 288 292 295 313   -hsync -vsync';
// Process the Modeline
generateModeline( $modeline );
