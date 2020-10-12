$(document).ready(() => {
    function bindAccordionDisabler() {
        $('.collapsible .collapsible-header').on('click', (event) => {
            console.log(event);
    
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
                .done((response) => {
                    console.info(response);

                    if (response.status == OK) {
                        console.info('postComment: alright, we got a response!');

                        $('#commentInput, #commentDeclaredName')
                            .val('')
                            .change();

                        commentsCollapsible = $('#commentsCollapsible');
                        commentsCollapsible.prepend(
                            getRenderedComment(response.result.id, declaredName, content, true)
                        );
                        
                        // Reset the container and the instance because of reasons.
                        commentsCollapsible.collapsible({ accordion: false });

                        bindAccordionDisabler();

                        toast('¡Listo! Publicaste tu comentario.');
                    } else {
                        toast('Algo anda mal, probá de nuevo.');
                    }
                })
                .always(() => {
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
    commentsCollapsible.collapsible({ accordion: false });

    bindAccordionDisabler();

    if (isOnline) {
        run('commentsManager', 'getComments', {
            message: MESSAGE,
            private: PRIVATE
        })
        .done((response) => {
            console.info(response);

            commentsCollapsible = $('#commentsCollapsible');

            switch (response.status) {
                case OK:
                    let renderedHTML = '';

                    response.result.forEach((comment) => {
                        renderedHTML += getRenderedComment(comment['id'], comment['declaredName'], comment['content'], true);
                    });

                    commentsCollapsible.append(renderedHTML);
                    commentsCollapsible.slideDown();

                    commentsCollapsible.collapsible({ accordion: false });

                    bindAccordionDisabler();
            
                    if (commentsCollapsible.find('li').length > 0) {
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
});