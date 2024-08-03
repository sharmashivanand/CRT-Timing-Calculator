<?php

class MonitorRange {
    public $hfreq_min, $hfreq_max;
    public $vfreq_min, $vfreq_max;
    public $supports_interlace;

    public function __construct($hfreq_min, $hfreq_max, $vfreq_min, $vfreq_max, $supports_interlace) {
        $this->hfreq_min = $hfreq_min;
        $this->hfreq_max = $hfreq_max;
        $this->vfreq_min = $vfreq_min;
        $this->vfreq_max = $vfreq_max;
        $this->supports_interlace = $supports_interlace;
    }
}

class Modeline {
    public $width, $height;
    public $refresh_rate;
    public $interlaced;

    public function __construct($width, $height, $refresh_rate, $interlaced) {
        $this->width = $width;
        $this->height = $height;
        $this->refresh_rate = $refresh_rate;
        $this->interlaced = $interlaced;
    }
}

function monitor_set_preset($type) {
    if ($type == "pal") {
        return new MonitorRange(15625.0, 15625.0, 50.0, 50.0, true);
    } elseif ($type == "ntsc") {
        return new MonitorRange(15734.0, 15734.0, 60.0, 60.0, true);
    } elseif ($type == "generic_60hz") {
        return new MonitorRange(30000.0, 80000.0, 59.0, 61.0, false);
    }
    return null;
}

function calculate_best_video_mode($range, $desired_mode) {
    if ($desired_mode->refresh_rate < $range->vfreq_min || $desired_mode->refresh_rate > $range->vfreq_max ||
        ($desired_mode->interlaced && !$range->supports_interlace)) {
        echo "Desired mode is out of range or not supported.\n";
        return null;
    }

    return $desired_mode;  // In a real application, adjustments would be applied here
}

$monitor_range = monitor_set_preset("generic_60hz");
if ($monitor_range === null) {
    echo "Failed to set monitor preset.\n";
    exit(1);
}

$desired_mode = new Modeline(1920, 1080, 60.0, false);
$best_mode = calculate_best_video_mode($monitor_range, $desired_mode);

if ($best_mode === null) {
    echo "No valid video mode found.\n";
    exit(1);
}

echo "Best Mode: " . $best_mode->width . "x" . $best_mode->height . "@" . $best_mode->refresh_rate . "Hz" .
     ($best_mode->interlaced ? " Interlaced" : "") . "\n";

?>
