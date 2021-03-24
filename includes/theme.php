<?php

$theme = getUserTheme();

foreach (THEME as $mode => $colors) {
    $mode = strtolower($mode);

    print '@media ';

    if (is_null($theme)) {
        print '(prefers-color-scheme: ' . $mode . ')';
    } else {
        switch ($theme) {
            case THEMES['LIGHT']:
                if ($mode == 'light') {
                    print 'screen /*--x-light*/';
                } else {
                    print '(/*--x-light*/)';
                }

                break;
            case THEMES['DARK']:
                if ($mode == 'dark') {
                    print 'screen /*--x-dark*/';
                } else {
                    print '(/*--x-dark*/)';
                }

                break;
        }
    }

    print '{';

    foreach (['' => 'color', 'bg' => 'background-color', 'border' => 'border-color'] as $prefix => $property) {
        if (strlen($prefix) > 0) {
            $prefix .= '-';
        }

        foreach ($colors as $index => $color) {
            print '.' . $prefix . $mode . '-' . ($index + 1) . '{' . $property . ':#' . $color . '!important;}';
        }

        $hex = str_split($colors[2], 2); $rgba = [];

        foreach ($hex as $pair) {
            $rgba[] = hexdec($pair);
        }

        // Rules taken from: https://stackoverflow.com/a/44417646.
        print preg_replace('/\s+/', ' ', '
            input:focus + label,
            .input-field input:focus + label,
            .materialize-textarea:focus:not([readonly]) + label {
                color: #' . $colors[8] . ' !important;
            }
            
            input:focus,
            .input-field input:focus,
            .materialize-textarea:focus:not([readonly]) {
                border-bottom: 1px solid #' . $colors[8] . ' !important;
                box-shadow: 0 1px 0 0 #' . $colors[8] . ' !important;
            }
            
            [type="checkbox"].filled-in:checked+span:not(.lever):after {
                background-color: #' . $colors[8] . ' !important;
                border-color: #' . $colors[8] . ' !important;
            }
            
            [type="radio"]:checked+span:after,
            [type="radio"].with-gap:checked+span:after {
                background-color: #' . $colors[8] . ';
            }
            
            [type="radio"]:checked+span:after, [type="radio"].with-gap:checked+span:before,
            [type="radio"].with-gap:checked+span:after {
                border: 2px solid #' . $colors[8] . ';
            }
            
            [type="radio"]:disabled+span {
                color: #' . $colors[7] . ';
            }

            [type="checkbox"]:checked+span:not(.lever):before {
                border-right: 2px solid #' . $colors[5] . ' !important;
                border-bottom: 2px solid #' . $colors[5] . ' !important;
            }

            [type="checkbox"].filled-in:checked+span:not(.lever):after {
                background-color: #' . $colors[4] . ' !important;
                border-color: #' . $colors[4] . ' !important;
            }
 
            .input-field .helper-text {
                color: #' . $colors[8] . ';
            }

            .btn:hover, .btn-large:hover, .btn-small:hover, .btn-floating:hover 
            .btn:focus, .btn-large:focus, .btn-small:focus, .btn-floating:focus {
                background-color: #' . $colors[5] . ';
            }
            
            .card-box { box-shadow: 1px 1px #' . $colors[9] . '; }
            
            .select-dropdown.dropdown-content li.selected {
                background-color: #' . $colors[11] . ';
            }
            
            .select-dropdown.dropdown-content li:hover {
                background-color: #' . $colors[11] . ';
            }

            body.keyboard-focused .select-dropdown.dropdown-content li:focus {
                background-color: #' . $colors[10] . ';
            }
            
            .dropdown-content {
                background-color: #' . $colors[10] . ';
            }'
        );
    }

    print '}';
}

?>

body, main, nav, .material-icons, button, .btn, #noInternetBtn {
    transition:
        color            <?= UI_SETTINGS['MATERIALIZE_TRANSITION_TIME'] / 1000 ?>s linear,
        background-color <?= UI_SETTINGS['MATERIALIZE_TRANSITION_TIME'] / 1000 ?>s linear,
        opacity          <?= UI_SETTINGS['MATERIALIZE_TRANSITION_TIME'] / 1000 ?>s linear;
}

.bg-nsfw { background-color: #<?= getColorForNSFW() ?> !important; }