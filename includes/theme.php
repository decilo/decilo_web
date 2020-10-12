<?php

foreach (THEME as $mode => $colors) {
    $mode = strtolower($mode);

    print '@media (prefers-color-scheme: ' . $mode . ') {';

    foreach (['' => 'color', 'bg' => 'background-color', 'border' => 'border-color'] as $prefix => $property) {
        if (strlen($prefix) > 0) {
            $prefix .= '-';
        }

        foreach ($colors as $index => $color) {
            print '.' . $prefix . $mode . '-' . ($index + 1) . ' { ' . $property . ': #' . $color . ' !important; }';
        }

        // Rules taken from: https://stackoverflow.com/a/44417646.
        print '
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
            
            .input-field .helper-text {
                color: #' . $colors[8] . ';
            }
            
            .btn:hover, .btn-large:hover, .btn-small:hover, .btn-floating:hover 
            .btn:focus, .btn-large:focus, .btn-small:focus, .btn-floating:focus {
                background-color: #' . $colors[5] . ';
            }
            
            .card-box { box-shadow: 1px 1px #' . $colors[9] . '; }';
    }

    print '}';
}

?>