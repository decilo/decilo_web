<?php

foreach (['' => 'color', 'bg' => 'background-color', 'border' => 'border-color'] as $prefix => $property) {
    if (strlen($prefix) > 0) {
        $prefix .= '-';
    }

    foreach (THEME as $index => $color) {
        print '.' . $prefix . 'dark-' . ($index + 1) . ' { ' . $property . ': #' . $color . ' !important; }';
    }
}

?>

/* Taken from: https://stackoverflow.com/a/44417646 */

input:focus + label,
.input-field input:focus + label,
.materialize-textarea:focus:not([readonly]) + label {
    color: #<?= THEME[4] ?> !important;
}

input:focus,
.input-field input:focus,
.materialize-textarea:focus:not([readonly]) {
    border-bottom: 1px solid #<?= THEME[4] ?> !important;
    box-shadow: 0 1px 0 0 #<?= THEME[4] ?> !important;
}
/* Taken from: https://stackoverflow.com/a/44417646 */

[type="checkbox"].filled-in:checked+span:not(.lever):after {
    background-color: #<?= THEME[4] ?> !important;
    border-color: #<?= THEME[4] ?> !important;
}

.input-field .helper-text {
    color: #<?= THEME[4] ?>;
}

.btn:hover, .btn-large:hover, .btn-small:hover, .btn-floating:hover 
.btn:focus, .btn-large:focus, .btn-small:focus, .btn-floating:focus {
    background-color: #<?= THEME[5] ?>;
}