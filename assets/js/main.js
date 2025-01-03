// eslint-disable-next-line no-undef
jQuery(document).ready(function ($) {
    const navIcon = $(".user_mobile_nav p span");

    $(".bbp-topic-freshness-author").each(function () {
        const $this = $(this);
        $this.html($this.html().replace(/&nbsp;/g, ""));
    });

    const fancybox = $(".fancybox");

    if (fancybox.length > 0) {
        fancyboxInit();
    }

    function fancyboxInit() {
        if (fancybox.length) {
            fancybox.click(function (e) {
                e.preventDefault();
                $.fancybox.open($("#email_join"));
                //$('#email_join').addClass('active');
            });

            fancybox.fancybox({
                arrows: false,
                autoSize: false,
                width: "750",
                height: "410",
                closeBtn: true,
                scrolling: "hidden",
                beforeShow() {
                    $("body").css({ "overflow-y": "hidden !important" });
                },
                afterClose() {
                    $("body").css({ "overflow-y": "visible" });
                },
                helpers: {
                    overlay: {
                        locked: true,
                    },
                },
            });
        }

        const fancybox2 = $(".fancybox2");

        if (fancybox2.length) {
            fancybox2.click(function (e) {
                e.preventDefault();
            });

            fancybox2.fancybox({
                arrows: false,
                autoSize: false,
                width: "750",
                height: "750",
                closeBtn: true,
                //scrolling: 'hidden',
                beforeShow() {
                    $("body").css({ "overflow-y": "hidden" });
                },
                afterClose() {
                    $("body").css({ "overflow-y": "visible" });
                },
                helpers: {
                    overlay: {
                        locked: true,
                    },
                },
            });
        }
    }

    $(".members_only_video_pop").fancybox({
        arrows: false,
        autoSize: false,
        width: "700",
        height: "300",
        closeBtn: true,
        //scrolling: 'hidden',
        beforeShow() {
            $("body").css({ "overflow-y": "hidden" });
        },
        afterClose() {
            $("body").css({ "overflow-y": "visible" });
        },
        helpers: {
            overlay: {
                locked: true,
            },
        },
    });

    if ($(window).width() > 768) {
        subMenuHover();
    } else if ($(window).width() < 769) {
        mobileSubMenu();
    }

    $(window).on("resize", function () {
        if ($(window).width() > 768) {
            $(".sub-menu").clearQueue();

            subMenuHover();

            if ($(".menu-item-has-children .sub-menu").hasClass("open")) {
                $(".menu-item-has-children .sub-menu").slideUp(100);
                $(".menu-item-has-children .sub-menu").removeClass("open");
            }

            if ($(".menu-item-has-children > a").hasClass("open")) {
                $(".menu-item-has-children > a").removeClass("open");
            }

            if ($(".wrapper").hasClass("slide")) {
                $(".mobile_menu_icon").removeClass("open");
                $(".wrapper").removeClass("slide");
            }

            //$('.nav_wrap').unbind('slideDown');
            $(".nav_wrap ul").removeClass("open");
            $(".user_mobile_nav").removeClass("open");
            $(".user_mobile_nav p span").removeClass("open");
            $(".nav_wrap ul").css("display", "block");
            navIcon.html("+");
        } else {
            $(".menu-item-has-children").unbind("mouseenter");
            $(".menu-item-has-children").unbind("mouseleave");

            mobileSubMenu();
        }

        const chatWindow = document.querySelector(
            ".live_stream .wp-block-columns"
        );
        if (chatWindow) {
            if ($(window).width() < 1023) {
                chatWindow.classList.remove("resize");
            } else {
                chatWindow.classList.add("resize");
            }
        }
    });

    function subMenuHover() {
        $(".menu-item-has-children").mouseenter(function () {
            $(this).children(".sub-menu").slideDown(100);
        });

        $(".menu-item-has-children").mouseleave(function () {
            $(this).children(".sub-menu").slideUp(100);
        });
    }

    function mobileSubMenu() {
        $(".menu-item-has-children > a").click(function (e) {
            if (!$(this).hasClass("open")) {
                e.preventDefault();
                $(this).next(".sub-menu").not(":animated").slideDown(400);
                $(this).addClass("open");
                $(this).parent("li").addClass("open");
            } else {
                e.preventDefault();
                $(this).next(".sub-menu").not(":animated").slideUp(400);
                $(this).removeClass("open");
                $(this).parent("li").removeClass("open");
            }
        });
    }

    $(".mobile_menu_icon").on("click", function (e) {
        e.preventDefault();

        $(this).toggleClass("open");
        $(".wrapper").toggleClass("slide");
        $("#global_header").toggleClass("slide");
        if ($(".mobile_menu_icon").hasClass("open")) {
            $("body, html").css("overflow-y", "hidden");
        } else {
            $("body, html").css("overflow-y", "auto");
        }
    });

    $(".wrapper.slide").click(function () {
        $(".mobile_menu_icon").toggleClass("open");
        $(".wrapper").toggleClass("slide");
    });

    ajaxMailChimpForm($("#subscribe-form"), $("#subscribe-result"));

    function ajaxMailChimpForm($form, $resultElement) {
        // Hijack the submission. We'll submit the form manually.
        $form.submit(function (e) {
            e.preventDefault();
            if (!isValidEmail($form)) {
                const error = "A valid email address must be provided.";
                $resultElement.html(error);
                $resultElement.css("color", "red");
            } else {
                $resultElement.css("color", "white");
                $resultElement.html("Subscribing...");
                submitSubscribeForm($form, $resultElement);
            }
        });
    }

    // Validate the email address in the form
    function isValidEmail($form) {
        // If email is empty, show error message.
        // contains just one @
        const email = $form.find("input[type='email']").val();
        if (!email || !email.length) {
            return false;
        } else if (email.indexOf("@") === -1) {
            return false;
        }
        return true;
    }

    // Submit the form with an ajax/jsonp request.
    // Based on http://stackoverflow.com/a/15120409/215821
    function submitSubscribeForm($form, $resultElement) {
        $.ajax({
            type: $form.attr("method"),
            url: $form.attr("action"),
            data: $form.serialize(),
            cache: false,
            dataType: "jsonp",
            jsonp: "c", // trigger MailChimp to return a JSONP response
            contentType: "application/json; charset=utf-8",
            error(error) {
                // According to jquery docs, this is never called for cross-domain JSONP requests
                // eslint-disable-next-line no-console
                console.log(error);
            },
            success(data) {
                if (data.result !== "success") {
                    let message =
                        data.msg ||
                        "Sorry. Unable to subscribe. Please try again later.";
                    $resultElement.css("color", "red");
                    if (
                        data.msg &&
                        data.msg.indexOf("already subscribed") >= 0
                    ) {
                        message =
                            "You're already on the list!<br>If you aren't getting messages, check your inbox and verify you confirmed your email!";
                        $resultElement.css("color", "white");
                    }
                    $resultElement.html(message);
                } else {
                    createCookie("subscribed", "success", 5000);

                    window.location.href =
                        "https://www.daricbennett.com/thank-you/";
                }
            },
        });
    }

    if ($(".mc4wp-response").find(".mc4wp-success").length > 0) {
        createCookie("subscribed_form", "success", 5000);
    }

    function createCookie(name, value, days) {
        let expires;
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
            expires = "; expires=" + date.toGMTString();
        } else {
            expires = "";
        }
        document.cookie = name + "=" + value + expires + "; path=/";
    }

    $(".accordion").on("click", function () {
        const headerHeight = $("#global_header").height();
        const accordion = $(this);
        const arrow = $(this).children(".arrow");
        const panel = $(this).next(".panel");
        const hash = $(this).children("a").attr("href");

        $(".accordion").not(this).removeClass("active");
        $(".arrow").removeClass("active");
        $(".panel").removeClass("show");

        if (accordion.hasClass("active")) {
            accordion.removeClass("active");
            arrow.removeClass("active");
            panel.removeClass("show");
        } else {
            accordion.addClass("active");
            arrow.addClass("active");
            panel.addClass("show");
        }

        setTimeout(function () {
            $("html,body").animate(
                { scrollTop: $(hash).offset().top - headerHeight },
                500
            );
        }, 1000);
    });

    $(".share_button").on("click", function () {
        this.nextElementSibling.classList.toggle("show");
    });

    if (window.location.hash) {
        let id = "";

        const hashTitle = window.location.hash.replace(
            /%..|[^a-zA-Z0-9-]/g,
            ""
        );

        if (hashTitle.indexOf("&") !== -1) {
            id = hashTitle.replace(/&/g, "and");
            $(id).click();
        } else if (hashTitle === "#update-password") {
            //hashID = hashTitle.replace('#','');
            $("html,body").animate(
                { scrollTop: $(hashTitle).offset().top - headerHeight },
                1000
            );
        } else if (hashTitle.indexOf("/") !== -1) {
            id = hashTitle.replace(/\//g, "-");
            $(id).click();
        } else {
            setTimeout(function () {
                $(window.location.hash).click();
            }, 10);
        }
    }

    $(window).on("scroll", function (event) {
        if ($(window).scrollTop() > 40) {
            $(
                ".header_top,.menu,#global_header .logo,.mobile_menu_icon,ul.member_menu > li"
            ).addClass("scroll");
            $(".header_bottom").addClass("home_background");
        } else {
            $(
                ".header_top,.menu,#global_header .logo,.mobile_menu_icon,ul.member_menu > li"
            ).removeClass("scroll");
            $(".header_bottom").removeClass("home_background");
        }
    });

    $(".user_mobile_nav").click(function () {
        if (!$(".nav_wrap ul").hasClass("open")) {
            //$('.nav_wrap ul').slideDown(400);
            $(".nav_wrap ul").addClass("open");
            $(".user_mobile_nav").addClass("open");
            $(".user_mobile_nav p span").addClass("open");
            navIcon.html("-");
        } else {
            //$('.nav_wrap ul').slideUp(400);
            $(".nav_wrap ul").removeClass("open");
            $(".user_mobile_nav p span").removeClass("open");
            setTimeout(function () {
                $(".user_mobile_nav").removeClass("open");
            }, 450);
            navIcon.html("+");
        }
    });

    $("#bbp_reply_submit, #bbp_topic_submit").click(function () {
        if ($("#rtmedia_uploader_filelist").is(":visible")) {
            $(".rtmedia-uploader-div .rtmedia-simple-file-upload").append(
                "Media Uploading...."
            );

            if (".rtmedia-container:contains('Media Uploading')") {
                $("#media_upload_wait").addClass("show");
                $("body, html").css("overflow-y", "hidden");
            }
        }
    });

    if ($(".mejs-overlay").length) {
        $(".mejs-overlay").html(
            "<p>Your file is converting, please be patient!<p>"
        );
    }

    const bbpContainer = $(".rtm-bbp-container");
    if (bbpContainer.length) {
        bbpContainer.parentsUntil(".odd").addClass("attach");
        bbpContainer.parentsUntil(".even").addClass("attach");
    }

    const youtube = document.querySelectorAll(".youtube_video");

    if (youtube) {
        for (let a = 0; a < youtube.length; a++) {
            youtube[a].addEventListener("click", function () {
                const iframe = document.createElement("iframe");

                iframe.setAttribute("frameborder", "0");
                iframe.setAttribute("allowfullscreen", "");
                iframe.setAttribute(
                    "src",
                    "https://www.youtube.com/embed/" +
                        this.dataset.embed +
                        "?rel=0&showinfo=0&autoplay=1"
                );

                this.innerHTML = "";
                this.appendChild(iframe);
            });
        }
    }

    const vimeo = document.querySelectorAll(".vimeo_video");

    if (vimeo.length) {
        for (let b = 0; b < vimeo.length; b++) {
            vimeo[b].addEventListener("click", function () {
                const iframe = document.createElement("iframe");

                iframe.setAttribute("frameborder", "0");
                iframe.setAttribute("allowfullscreen", "");
                iframe.setAttribute(
                    "src",
                    "https://player.vimeo.com/video/" +
                        this.dataset.embed +
                        "?autoplay=1"
                );

                this.innerHTML = "";
                this.appendChild(iframe);
            });
        }
    }

    const soundslice = document.querySelectorAll(".soundslice_video");

    if (soundslice.length) {
        for (let c = 0; c < soundslice.length; c++) {
            soundslice[c].addEventListener("click", function () {
                const iframe = document.createElement("iframe");

                iframe.setAttribute("id", "ssembed");
                iframe.setAttribute("frameborder", "0");
                iframe.setAttribute("allowfullscreen", "");
                iframe.setAttribute(
                    "src",
                    "https://www.soundslice.com/scores/" + this.dataset.embed
                );

                this.innerHTML = "";
                this.appendChild(iframe);
            });
        }
    }

    $(".keyboard_link").click(function (e) {
        e.preventDefault();

        $(".keyboard_popup").css("display", "block");

        $(".keyboard_popup iframe").attr(
            "src",
            "https://www.soundslice.com/scores/" + this.dataset.embed
        );

        $("body, html").css("overflow-y", "hidden");
    });

    $(".keyboard_popup .close_button").click(function (e) {
        $(".keyboard_popup").css("display", "none");
        $(".keyboard_popup iframe").attr("src", "");
        $("body, html").css("overflow-y", "scroll");
    });

    // eslint-disable-next-line no-undef
    const pageURL = currentPage.postSlug;

    if (
        currentPage.postType &&
        (currentPage.postType === "videos" ||
            currentPage.postType === "live-streams" ||
            pageURL.includes("free-bass-lessons"))
    ) {
        commentVideoEmbed();
    }

    function commentVideoEmbed() {
        if ($(".comment-content > p a").length) {
            const links = document.querySelectorAll(".comment-content > p a");

            for (x = 0; x < links.length; x++) {
                const videoLink = $(links[x]).attr("href");
                var embedLink;
                var str;

                if (videoLink.includes("embed")) {
                    embedLink = videoLink + "/?rel=0&showinfo=0";
                } else if (videoLink.includes("v=")) {
                    str = videoLink.split("v=");
                    embedLink =
                        "https://www.youtube.com/embed/" +
                        str[1] +
                        "/?rel=0&showinfo=0";
                } else if (videoLink.includes("youtu.be")) {
                    str = videoLink.split(".be/");
                    embedLink =
                        "https://www.youtube.com/embed/" +
                        str[1] +
                        "/?rel=0&showinfo=0";
                } else if (videoLink.includes("vimeo")) {
                    str = videoLink.split("video/");
                    embedLink = "https://player.vimeo.com/video/" + str[1];
                } else {
                    embedLink = "";
                }

                if (embedLink !== "") {
                    $(
                        "<div class='video_embed'><div class='video_wrapper'><iframe frameborder='0' allowfullscreen src='" +
                            embedLink +
                            "'></iframe></div></div>"
                    ).insertAfter($(links[x]).parent());

                    links[x].replaceWith("");
                }
            }
        }

        if ($(".comment-content > p").length) {
            const commentContent = document.querySelectorAll(
                ".comment-content > p"
            );

            for (y = 0; y < commentContent.length; y++) {
                const commentText = commentContent[y].innerHTML;

                if (commentText.includes("http")) {
                    let string = commentText.split("http");
                    string = string[1].replace(/\s/g, "");

                    const newVideoLink = "http" + string;
                    let newEmbedLink = "";

                    if (newVideoLink.includes("embed")) {
                        newEmbedLink = newVideoLink;
                    } else if (newVideoLink.includes("v=")) {
                        str = newVideoLink.split("v=");
                        newEmbedLink =
                            "https://www.youtube.com/embed/" + str[1];
                    } else if (newVideoLink.includes("youtu.be")) {
                        str = newVideoLink.split(".be/");
                        newEmbedLink =
                            "https://www.youtube.com/embed/" + str[1];
                    } else {
                        newEmbedLink = "";
                    }

                    if (newEmbedLink !== "") {
                        $(
                            "<div class='video_embed'><div class='video_wrapper'><iframe frameborder='0' allowfullscreen src='" +
                                newEmbedLink +
                                "/?rel=0&showinfo=0'></iframe></div></div>"
                        ).insertAfter($(commentContent[y]));
                    }
                }
            }
        }
    }

    //var ajaxComments = null;
    const commentReply = $("a.comment-reply-link");
    let commentParent = 0;
    let replyToUser = null;
    //let commentReplyURL = null;

    if (commentReply.length) {
        replyToComment(commentReply);
    }

    function replyToComment(commentReplyProp) {
        commentReplyProp.prop("onclick", null).off("click");

        commentReplyProp.click(function (e) {
            e.preventDefault();

            if ($(".comment_reply_wrap").hasClass("open")) {
                $(".comment_reply_wrap").removeClass("open").slideUp(600);
                $(".reply_button").css("display", "inline-block");

                if (
                    currentPage.pageName === "Lessons" ||
                    currentPage.postType === "courses"
                ) {
                    $("#respond").remove();
                }
            }

            $(this).parent().css("display", "none");
            $(this)
                .parent()
                .next(".comment_reply_wrap")
                .addClass("open")
                .slideDown(600);

            replyToUser = $(this).attr("aria-label").split("to");
            replyToUser = replyToUser[1].trim();

            //commentReplyURL = window.location.href;

            commentParent = parseInt(
                $(this).closest("li.comment").attr("id").replace(/[^\d]/g, ""),
                10
            );

            // eslint-disable-next-line no-undef
            if (
                currentPage.pageName === "Lessons" ||
                (currentPage.postType && currentPage.postType === "courses")
            ) {
                const postID = $(this).data("postid");
                const ajaxURL = myAjaxurl.ajaxurl;
                const commentForm = $.ajax({
                    type: "post",
                    dataType: "html",
                    data: { action: "get_comment_form", id: postID },
                    url: ajaxURL,
                    global: false,
                    async: false,
                    success(response) {
                        //alert ("Email Sent");
                        return response;
                    },
                    error(xhRequest, errorThrown, resp) {
                        console.error(errorThrown);
                        console.error(JSON.stringify(resp));
                    },
                }).responseText;

                $(commentForm).insertBefore(
                    $(this)
                        .parent()
                        .next(".comment_reply_wrap")
                        .children(".cancel_comment")
                );
            }

            $(this)
                .closest(".reply")
                .find("#comment_parent")
                .val(commentParent);
        });
    }

    if ($(".cancel_comment").length) {
        commentCancel();
    }

    function commentCancel() {
        $(".cancel_comment a").bind("click", function (e) {
            e.preventDefault();
            if ($(".comment_reply_wrap").hasClass("open")) {
                $(".comment_reply_wrap").removeClass("open").slideUp(600);
                commentParent = 0;
                //commentReplyURL = null;
                replyToUser = null;
                //commentSubmitButton.next('.loading_gif').html('');
                $(this)
                    .closest(".reply")
                    .children(".reply_button")
                    .css("display", "block");

                // eslint-disable-next-line no-undef
                if (
                    currentPage.pageName === "Lessons" ||
                    (currentPage.postType && currentPage.postType === "courses")
                ) {
                    $(this).parent().parent().children("#respond").remove();
                }
            }
        });
    }

    if (currentPage.pageName === "Lessons") {
        $(".filtr-container").filterizr({
            layout: "sameSize",
        });
    }

    $(".filter_list li").click(function () {
        if (!$(this).hasClass("all")) {
            $(this).toggleClass("active");
            $(".filter_list li.all").removeClass("active");

            const allFilters = document.querySelectorAll(".filter_list li");

            let active = false;
            for (let i = 0; i < allFilters.length; i++) {
                if (allFilters[i].classList.contains("active")) {
                    active = true;
                }
            }

            if (active === false) {
                $(".filter_list li.all").addClass("active");
            }
        } else {
            $(".filter_list li").removeClass("active");
            $(this).addClass("active");
        }
    });

    $(".play_video").on("click", function () {
        let videoPlayer = "";
        const htmlBody = $("html, body");
        const clickHash = $(this).attr("href");

        createCookie("clickHash", clickHash, 5);

        if (currentPage.postType && currentPage.postType !== "courses") {
            htmlBody.animate(
                {
                    scrollTop:
                        $("#video_player").offset().top -
                        $("#global_header").height(),
                },
                1000
            );
        } else {
            const clickSlug = currentPage.postSlug
                .split("courses/")[1]
                .replace("/", "");
            console.log("postSlug: ", clickSlug);
            createCookie("clickSlug", clickSlug);

            var hash = clickHash + "-video";
            videoPlayer = $(this)
                .closest(".row")
                .children(".course_video_player");
        }

        const videoSrc = $(this).data("src");
        const videoType = $(this).data("type");
        const replaceVideoLink = $(this).data("replace");
        const videoTitle = $(this).data("title");
        const notation = $(this).data("notation");
        const postID = $(this).data("postid");
        const desc = $(this).children(".lesson_description").html();
        let favoriteButton = "";
        // eslint-disable-next-line no-undef
        const ajaxURL = myAjaxurl.ajaxurl;

        let videoDesc = "";

        if (desc) {
            videoDesc =
                '<div class="full_width description">' + desc + "</div>";
        }

        if (currentPage.postType && currentPage.postType !== "courses") {
            videoPlayer = $("#video_player").empty();
            videoPlayer.addClass("open");
        } else {
            $(".course_video_player").empty().removeClass("open");
            $(".lessons_page.courses .row").removeClass("open_player");
            $(this).closest(".row").addClass("open_player");
            videoPlayer.addClass("open");
        }

        if (currentPage.postType && currentPage.postType !== "courses") {
            favoriteButton = $(this).parent().children(".button_wrap").html();
        } else {
            favoriteButton = $(this).parent().prev(".button_wrap").html();
        }

        if ($(this).parent().parent().children(".video_files").length) {
            const files = $(this).parent().parent().children(".video_files");

            var fileElements = "";

            for (let i = 0; i < files.length; i++) {
                fileElements +=
                    '<a target="_blank" href="' +
                    files[i].dataset.file +
                    '">' +
                    files[i].dataset.text +
                    "</a>";
            }
        } else {
            fileElements = "";
        }

        commentsAjaxCall(ajaxURL, postID).then(
            function (response) {
                const commentContent = response;

                if (videoType === "soundslice_video") {
                    var replaceVideo =
                        '<div class="link_wrap"><p class="replace_link">Video trouble? <a class="replace_video" href="#" data-replace="' +
                        replaceVideoLink +
                        '">Use this LINK!</a></p></div>';
                } else {
                    replaceVideo = "";
                    //keyboardLink = "";
                }

                let html =
                    '<div class="full_width lesson_title">' +
                    "<h3>" +
                    videoTitle +
                    "</h3>" +
                    "</div>" +
                    '<div class="content_wrap full_width">' +
                    '<div class="video_iframe_wrap">' +
                    '<div class="top_row">' +
                    '<div class="button_wrap">' +
                    favoriteButton +
                    "</div>";

                if (fileElements) {
                    html +=
                        '<div class="links_wrap">' + fileElements + "</div>";
                }

                html += replaceVideo + "</div>";

                if (notation === "yes") {
                    html += '<div class="video_wrapper video_notation">';
                } else {
                    html += '<div class="video_wrapper">';
                }

                html +=
                    '<iframe frameborder="0" allowfullscreen src="' +
                    videoSrc +
                    '"></iframe>' +
                    "</div>" +
                    videoDesc +
                    "</div>" +
                    '<div class="video_content_wrap">' +
                    '<div id="comments" class="comments-area">' +
                    '<ol class="comment-list">' +
                    commentContent +
                    "</ol>" +
                    "</div>" +
                    "</div>" +
                    "</div>";

                $(html)
                    .hide()
                    .appendTo(videoPlayer)
                    .slideDown(1000, function () {
                        if (
                            currentPage.postType &&
                            currentPage.postType === "courses"
                        ) {
                            htmlBody.animate(
                                {
                                    scrollTop:
                                        $(hash).offset().top -
                                        $("#global_header").height(),
                                },
                                500
                            );
                        }
                    });

                $(".replace_video").bind("click", function (e) {
                    e.preventDefault();
                    $(".video_wrapper").removeClass("video_notation");
                    const vimeoLink = $(this).data("replace");
                    $(this)
                        .parent()
                        .nextAll(".video_wrapper")
                        .find("iframe")
                        .attr("src", vimeoLink);
                });

                replyToComment($("a.comment-reply-link"));
                commentCancel();
            },
            function (reason) {
                console.log("error", reason);
            }
        );

        setTimeout(function () {
            commentVideoEmbed();
        }, 3000);
    });

    function commentsAjaxCall(url, postid) {
        return $.ajax({
            method: "post",
            dataType: "html",
            data: { action: "get_lesson_comments", id: postid },
            url,
        });
    }

    const fullURL = window.location.href;

    if (fullURL.includes("filter=inbox")) {
        $("#fep-menu-message_box").addClass("fep-button-active");
    } else {
        $("#fep-menu-message_box").removeClass("fep-button-active");
    }

    const postVideoBtn = document.getElementById("post_video_btn");
    const cancelPost = $(".cancel_post");

    if (postVideoBtn) {
        $("#post_video_btn").click(function (e) {
            e.preventDefault();
            const headerHeight = $("#global_header").height();

            $("#post_submit_form").addClass("show");
            $("html,body").animate(
                { scrollTop: $(this).offset().top - headerHeight },
                1000
            );

            $("#post_video_btn").css("opacity", "0");
        });
    }

    if (cancelPost) {
        cancelPost.click(function (e) {
            e.preventDefault();
            $("#post_submit_form").removeClass("show");
            $("html, body").animate({ scrollTop: 100 }, "slow");

            setTimeout(function () {
                $("#post_video_btn").css("opacity", "1");
            }, 800);
        });
    }

    const fbGroup = document.querySelector(".fb-group");
    if (fbGroup) {
        if ($(window).width() < 500) {
            fbGroup.dataset.width = 350;
        }

        $(window).on("resize", function () {
            if ($(window).width() < 500) {
                fbGroup.dataset.width = 350;
            } else {
                fbGroup.dataset.width = 500;
            }
        });
    }

    const bcountryDiv = jQuery('label[for="bcountry"]').closest("div");
    bcountryDiv.insertAfter(jQuery("#country_drop").closest("div"));

    if ($(window).width() > 768) {
        const facadePlay = document.querySelector(
            ".live_stream .columns_wrap .epyt-facade-play"
        );
        if (facadePlay) {
            facadePlay.addEventListener("click", () => {
                setTimeout(() => {
                    const streamVideo = document.querySelector(
                        ".live_stream .columns_wrap .epyt-video-wrapper"
                    );
                    if (streamVideo) {
                        streamVideo.style.paddingTop = "36.206897%";
                    }
                }, 500);
            });
        }
    }

    const chatWindow = document.querySelector(".live_stream .wp-block-columns");
    if (chatWindow && $(window).width() > 1023) {
        chatWindow.classList.add("resize");
    }
});
