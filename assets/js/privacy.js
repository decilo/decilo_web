$(document).ready(() => {
    $('#gdprComplianceCheckbox')
        .prop('checked', 
            acceptedGDPR != null && acceptedGDPR != 'false'
        )
        .on('change', (event) => {
            localStorage.setItem(
                GDPR_COMPLICANCE_KEY, $(event.target).prop('checked')
            );

            toast('¡Guardamos tu preferencia!');
        });
});