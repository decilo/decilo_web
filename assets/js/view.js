$(document).ready(() => {
    if (getStatusForNSFW() && localStorage.getItem('canSeeNSFW') == null) {
        animateRedirect(SYSTEM_HOSTNAME + 'exceptions/forbidden');
    }

    function bindAccordionDisabler() {
        $('.collapsible .collapsible-header').on('click', (event) => {
            event.stopPropagation();
        });
    }

    $('#sendCommentBtn').on('click', () => {
        content         = $('#commentInput').val();
        declaredName    = $('#commentDeclaredName').val();
        declaredName    = declaredName.length > 0 ? declaredName : null;

        if (content.length > 0) {
            if (isOnline) {
                run('commentsManager', 'postComment', {
                    content:        content,
                    declaredName:   declaredName,
                    message:        MESSAGE,
                    private:        PRIVATE
                }, () => {
                    disable($('#commentInput, #commentDeclaredName, #sendCommentBtn'));
                })
                .then((response) => {
                    console.info(response);

                    if (response.data.status == OK) {
                        console.info('postComment: alright, we got a response!');

                        $('#commentInput, #commentDeclaredName')
                            .val('')
                            .trigger('change');

                        commentsCollapsible = $('#commentsCollapsible');
                        commentsCollapsible.prepend(
                            getRenderedComment(response.data.result.id, declaredName, content, true)
                        );
                        
                        commentsCollapsible = M.Collapsible.init(commentsCollapsible[0], { accordion: false });
                        commentsCollapsible.open(0);

                        bindAccordionDisabler();

                        toast('¡Listo! Publicaste tu comentario.');
                    } else {
                        toast('Algo anda mal, probá de nuevo.');
                    }
                })
                .then(() => {
                    enable($('#commentInput, #commentDeclaredName, #sendCommentBtn'));
                });
            } else {
                if ($('.toast').length < 1) {
                    toast(NO_INTERNET_HINT);
                }
            }

            $('#commentInput')
                .removeClass('invalid')
                .addClass('valid');
        } else {
            $('#commentInput')
                .addClass('invalid')
                .removeClass('valid');
        }
    });

    commentsCollapsible = $('#commentsCollapsible');
    commentsCollapsible = M.Collapsible.init(commentsCollapsible[0], { accordion: false });

    bindAccordionDisabler();

    if (isOnline) {
        run('commentsManager', 'getComments', {
            message: MESSAGE,
            private: PRIVATE
        })
        .then((response) => {
            console.info(response);

            commentsCollapsible = $('#commentsCollapsible');

            switch (response.data.status) {
                case OK:
                    let renderedHTML = '';

                    response.data.result.forEach((comment) => {
                        renderedHTML += getRenderedComment(comment['id'], comment['declaredName'], comment['content'], true, comment['likes']);
                    });

                    commentsCollapsible.append(renderedHTML);

                    commentsCollapsible = M.Collapsible.init(commentsCollapsible[0], { accordion: false });

                    bindAccordionDisabler();
            
                    if ($(commentsCollapsible.el).find('li').length > 0) {
                        M.Collapsible
                            .getInstance($('#commentsCollapsible'))
                            .open(0);
                    }

                    break;
            }
        });
    } else {
        if ($('.toast').length < 1) {
            toast(NO_INTERNET_HINT);
        }
    }

    messageContent = $('#messageContent');

    messageContent.html(
        convertMDtoHTML(messageContent.html().trim())
    );
});