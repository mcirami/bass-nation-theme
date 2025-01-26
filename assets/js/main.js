"use strict";
const { filter } = require("lodash");
const { Swiper } = require("swiper/bundle");
jQuery.noConflict();
// eslint-disable-next-line no-undef
jQuery(document).ready(function ($) {
    window.addEventListener("error", (event) => {
        console.error("Global Error:", event.error || event.message);
    });

    window.addEventListener("unhandledrejection", (event) => {
        console.error("Unhandled Promise Rejection:", event.reason);
    });

    const navIcon = $(".user_mobile_nav p span");
    const videoPlayer = document.querySelector("#video_player");

    $(".bbp-topic-freshness-author").each(function () {
        const $this = $(this);
        $this.html($this.html().replace(/&nbsp;/g, ""));
    });

    const fancybox = $(".fancybox");
    const fancybox2 = $(".fancybox2");

    fancyboxInit();

    function fancyboxInit() {
        if (fancybox.length > 0) {
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

        if (fancybox2.length > 0) {
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

    const membersOnlyPop = document.querySelectorAll(".members_only_video_pop");
    if (membersOnlyPop.length > 0) {
        membersOnlyPop.forEach((element) => {
            element.addEventListener("click", (e) => {
                e.preventDefault();
                const videoPop = document.querySelector(
                    "#members_only_video_pop"
                );
                videoPop.classList.add("open");
                videoPop.addEventListener("click", (e) => {
                    videoPop.classList.remove("open");
                });
            });
        });
    }

    if ($(window).width() > 768) {
        subMenuHover();
    } else if ($(window).width() < 769) {
        mobileSubMenu();
    }

    $(window).on("resize", function () {
        if ($(window).width() > 991) {
            $(".sub-menu").clearQueue();
            $("#global_header").removeClass("slide");
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
            var headerHeight = $(".header_bottom").height();
            $("#global_header").css("background", "#000000");
            const top = headerHeight - 50;
            $(this)
                .children(".sub-menu")
                .css("top", top + "px");
        });

        $(".menu-item-has-children").mouseleave(function () {
            $(this).children(".sub-menu").slideUp(100);
            $("#global_header").css("background", "unset");
        });
    }

    function mobileSubMenu() {
        $(".menu-item-has-children > a").click(function (e) {
            if (!$(this).hasClass("open")) {
                e.preventDefault();
                if ($(".menu-item-has-children > a").hasClass("open")) {
                    $(".menu-item-has-children > a").removeClass("open");
                }
                if (
                    $(".menu-item-has-children > a")
                        .parent("li")
                        .hasClass("open")
                ) {
                    $(".menu-item-has-children > a")
                        .parent("li.open")
                        .children(".sub-menu")
                        .slideUp(400);
                    $(".menu-item-has-children > a")
                        .parent("li")
                        .removeClass("open");
                }

                $(this).addClass("open");
                $(this).next(".sub-menu").not(":animated").slideDown(400);
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
            var headerHeight = $("#global_header").height();
            $(".menu").css("top", headerHeight + "px");
        } else {
            $("body, html").css("overflow-y", "auto");
            setTimeout(() => {
                $(".menu").css("top", "unset");
            }, 500);
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

        const hashTitle = window.location.hash;

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

    // Throttle function to limit the number of times a function is called
    function throttle(func, limit) {
        let lastFunc;
        let lastRan;
        return function () {
            const context = this;
            const args = arguments;
            if (!lastRan) {
                func.apply(context, args);
                lastRan = Date.now();
            } else {
                clearTimeout(lastFunc);
                lastFunc = setTimeout(
                    function () {
                        if (Date.now() - lastRan >= limit) {
                            func.apply(context, args);
                            lastRan = Date.now();
                        }
                    },
                    limit - (Date.now() - lastRan)
                );
            }
        };
    }

    function videoScrollAction() {
        const videoWrapper = $(".video_iframe_wrap");
        const videoPlayerTwo = document.getElementById("video_player");

        if (videoPlayerTwo.scrollTop > videoWrapper.offset().top) {
            videoPlayer.classList.add("scroll");
        } else {
            videoPlayer.classList.remove("scroll");
            videoPlayer.style.paddingTop = 0;
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

    const pageURL = currentPage.postSlug;

    if (
        pageURL.includes("video") ||
        pageURL.includes("lesson") ||
        pageURL.includes("bass-nation-tv")
    ) {
        commentVideoEmbed();
    }

    function commentVideoEmbed() {
        if ($(".comment-content .bottom_section p a").length > 0) {
            const links = document.querySelectorAll(
                ".comment-content .bottom_section p a"
            );

            for (let x = 0; x < links.length; x++) {
                const videoLink = $(links[x]).attr("href");
                let embedLink = "";
                let str = "";

                if (
                    videoLink.includes("embed") ||
                    videoLink.includes("player.vimeo")
                ) {
                    embedLink = videoLink;
                } else if (videoLink.includes("v=")) {
                    str = videoLink.split("v=")[1];
                    if (str.includes("&")) {
                        str = str.split("&")[0];
                    }
                    embedLink = "https://www.youtube.com/embed/" + str;
                } else if (videoLink.includes("youtu.be")) {
                    str = videoLink.split(".be/")[1];
                    embedLink = "https://www.youtube.com/embed/" + str;
                } else if (
                    videoLink.includes("vimeo") &&
                    !videoLink.includes("player")
                ) {
                    str = videoLink.split("vimeo.com/");
                    embedLink = "https://player.vimeo.com/video/" + str[1];
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

        if ($(".comment-content .bottom_section p").length > 0) {
            const commentContent = document.querySelectorAll(
                ".comment-content .bottom_section p"
            );

            for (var y = 0; y < commentContent.length; y++) {
                const commentText = commentContent[y].innerHTML;
                let newEmbedLink = "";
                let str = "";

                if (
                    commentText.includes("v=") &&
                    commentText.includes("youtube")
                ) {
                    str = commentText.split("v=")[1];
                    if (str.includes("&")) {
                        str = str.split("&")[0];
                    }
                    newEmbedLink = "https://www.youtube.com/embed/" + str;
                } else if (commentText.includes("youtu.be")) {
                    str = commentText.split(".be/")[1];
                    newEmbedLink = "https://www.youtube.com/embed/" + str;
                } else if (
                    commentText.includes("vimeo") &&
                    !commentText.includes("player")
                ) {
                    str = commentText.split("vimeo.com/");
                    newEmbedLink = "https://player.vimeo.com/video/" + str[1];
                } else if (commentText.includes("player.vimeo")) {
                    str = commentText.split("video/")[1];
                    newEmbedLink = "https://player.vimeo.com/video/" + str;
                }

                if (newEmbedLink !== "") {
                    $(
                        "<div class='video_embed'><div class='video_wrapper'><iframe frameborder='0' allowfullscreen src='" +
                            newEmbedLink +
                            "'></iframe></div></div>"
                    ).insertAfter($(commentContent[y]));
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

            setTimeout(() => {
                if ($(".comment_reply_wrap").hasClass("open")) {
                    $(".comment_reply_wrap").removeClass("open");
                    $(".reply_button").css("opacity", "100%");
                    $("#respond").remove();
                }

                $(this).parent().css("opacity", "0");
                $(this)
                    .parents(".comment-metadata")
                    .children(".comment_reply_wrap")
                    .addClass("open");

                replyToUser = $(this).attr("aria-label").split("to");
                replyToUser = replyToUser[1].trim();

                //commentReplyURL = window.location.href;

                commentParent = parseInt(
                    $(this)
                        .closest("li.comment")
                        .attr("id")
                        .replace(/[^\d]/g, ""),
                    10
                );

                // eslint-disable-next-line no-undef

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
                        .parents(".comment-metadata")
                        .children(".comment_reply_wrap")
                        .children(".cancel_comment")
                );

                $(this)
                    .closest(".reply")
                    .find("#comment_parent")
                    .val(commentParent);
            }, 300);
        });
    }

    if ($(".cancel_comment").length) {
        commentCancel();
    }

    function commentCancel() {
        $(".cancel_comment a").bind("click", function (e) {
            e.preventDefault();
            if ($(".comment_reply_wrap").hasClass("open")) {
                $(".comment_reply_wrap").removeClass("open");
                commentParent = 0;
                //commentReplyURL = null;
                replyToUser = null;
                //commentSubmitButton.next('.loading_gif').html('');
                $(".reply_button").css("opacity", "100%");

                setTimeout(() => {
                    if (
                        currentPage.pageName === "Lessons" ||
                        (currentPage.postType &&
                            currentPage.postType === "courses")
                    ) {
                        $(this).parent().parent().children("#respond").remove();
                    }
                }, 800);
            }
        });
    }

    if (
        currentPage.pageName === "Lessons" ||
        currentPage.pageId == 7 ||
        currentPage.pageName === "Courses"
    ) {
        const itemsPerPage = 200;
        let filterContainer = document.querySelector("#filter_images");
        let currentPage = 1;
        let allItems = [];
        let activeCategories = []; // Tracks selected categories
        let originalItems = [];

        function initializeItems() {
            // Get all items dynamically from the page

            if (originalItems.length == 0) {
                originalItems = Array.from(
                    document.querySelectorAll(".filtr-item")
                );
            } else {
                allItems = [];
            }

            originalItems.forEach((item) => {
                item.style.display = "none";
                allItems.push(item);
            });

            filterContainer.innerHTML = "";
        }

        function renderItems(filteredData) {
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;

            let left = 200;
            let count = 0;
            let module = 0;

            if (window.innerWidth < 551) {
                module = 1;
            } else if (window.innerWidth < 768) {
                module = 2;
            } else if (window.innerWidth < 992) {
                module = 3;
            } else {
                module = 4;
            }
            // Show only items for the current page
            const pageItems = filteredData.slice(start, end);
            pageItems.forEach((item) => {
                ++count;
                item.classList.remove("show");
                item.style.left = left + "px";
                if (count % 2 === 0) {
                    item.style.bottom = 40 + "px";
                } else {
                    item.style.top = 40 + "px";
                }
                item.style.display = ""; // Show
                filterContainer.appendChild(item);
                setTimeout(() => {
                    item.classList.add("show");
                    item.style.left = 0;
                    item.style.top = 0;
                    item.style.bottom = 0;
                }, 100);

                if (module === 1 || count % module === 0) {
                    left = 200;
                } else {
                    left -= 50;
                }
            });
        }

        function renderPagination(totalItems) {
            const pagination = document.getElementById("pagination");
            pagination.innerHTML = "";

            const totalPages = Math.ceil(totalItems / itemsPerPage);

            for (let i = 1; i <= totalPages; i++) {
                const button = document.createElement("button");
                button.textContent = i;
                button.classList.toggle("active", i === currentPage);
                button.addEventListener("click", () => {
                    currentPage = i;
                    initializeItems();
                    applyFiltersAndRender();
                    window.scrollTo({
                        top: 0,
                        left: 0,
                        behavior: "smooth", // Optional for smooth scrolling
                    });
                });
                pagination.appendChild(button);
            }
        }

        function applyFiltersAndRender() {
            const searchInput = document
                .getElementById("search_input")
                .value.toLowerCase();

            const filteredData = allItems.filter((item) => {
                const matchesSearch = item.textContent
                    .trim()
                    .toLowerCase()
                    .includes(searchInput);
                const matchesFilter =
                    activeCategories.length === 0 ||
                    activeCategories.some((value) => {
                        if (item.dataset.groups.includes(value)) {
                            return item;
                        }
                    });

                return matchesSearch && matchesFilter;
            });
            let allItemsCopy = [...filteredData];

            // Filter items
            // If the current page has no items after filtering, reset to the last valid page
            const totalPages = Math.ceil(allItemsCopy.length / itemsPerPage);
            if (currentPage > totalPages) {
                currentPage = totalPages || 1; // Reset to 1 if no items match
            }

            renderItems(allItemsCopy);
            renderPagination(allItemsCopy.length);
        }

        function handleCategoryToggle(category) {
            if (category === "all") {
                document.querySelectorAll("li.filter_button").forEach((el) => {
                    el.classList.remove("active");
                });
                activeCategories = [];
            } else {
                const index = activeCategories.indexOf(parseInt(category));

                // Add or remove category from activeCategories
                if (index === -1) {
                    activeCategories.push(parseInt(category));
                    document.querySelector("li.all").classList.remove("active");
                } else {
                    activeCategories.splice(index, 1);
                }

                // Update button appearance
                const button = document.querySelector(
                    `li[data-group="${parseInt(category)}"]`
                );
                button.classList.toggle("active", index === -1);
            }

            if (activeCategories.length < 1) {
                document.querySelector("li.all").classList.add("active");
            }

            // Reset to first page and re-render
            currentPage = 1;
            initializeItems();
            applyFiltersAndRender();
        }

        function initializeFilters() {
            const filterButtons = document.querySelectorAll(".filter_button");
            filterButtons.forEach((button) => {
                button.addEventListener("click", (event) => {
                    const category = event.target.dataset.group;
                    if (category) {
                        handleCategoryToggle(category);
                    }
                });
            });
        }

        function debounce(func, delay) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        const handleSearchInput = debounce(() => {
            applyFiltersAndRender();
        }, 500);

        // Event listeners
        document
            .getElementById("search_input")
            .addEventListener("input", () => {
                currentPage = 1; // Reset to first page
                initializeItems();
                //applyFiltersAndRender();
                handleSearchInput();
            });

        // Initial setup
        initializeItems();
        initializeFilters();
        applyFiltersAndRender();
    }

    $(".play_video").on("click", function () {
        let videoPlayer = "";
        const htmlBody = $("html, body");
        const clickHash = $(this).attr("href");
        $("body, html").css("overflow-y", "hidden");
        document.querySelector("#global_header").style.zIndex = 9;

        createCookie("clickHash", clickHash, 5);

        const videoSrc = $(this).data("src");
        const videoType = $(this).data("type");
        const replaceVideoLink = $(this).data("replace");
        const videoTitle = $(this).data("title");
        const notation = $(this).data("notation");
        const postID = $(this).data("postid");
        const desc = $(this).data("desc");
        const files = $(this).data("files");
        const permalink = $(this).data("permalink");

        let favoriteButton = "";

        let videoDesc = "";

        if (desc) {
            videoDesc = '<div class="description"><p>' + desc + "</p></div>";
        }

        videoPlayer = $("#video_player").empty();
        videoPlayer.addClass("open");

        favoriteButton = $(this).parent().children(".button_wrap").html();

        let fileElements = "";

        if (files.length > 0 && currentPage.pageId !== 7) {
            files.forEach((file) => {
                if (file["file"]) {
                    fileElements +=
                        '<div class="column"><a target="_blank" href="' +
                        file["file"] +
                        '">' +
                        file["text"] +
                        "</a></div>";
                }
            });
        }

        if (currentPage.pageId == 7) {
            let videoShareColumn = document.getElementById(
                "free_video_share_column"
            );
            videoShareColumn.childNodes[3].lastElementChild.lastElementChild.href =
                permalink;
            const videoShareHTML = videoShareColumn.innerHTML;
            getVideoHTML(
                videoTitle,
                videoSrc,
                favoriteButton,
                fileElements,
                videoDesc,
                false,
                videoPlayer,
                videoShareHTML
            );
        } else {
            const ajaxURL = myAjaxurl.ajaxurl;
            commentsAjaxCall(ajaxURL, postID).then(
                function (response) {
                    getVideoHTML(
                        videoTitle,
                        videoSrc,
                        favoriteButton,
                        fileElements,
                        videoDesc,
                        response,
                        videoPlayer
                    );
                },
                function (reason) {
                    console.log("error", reason);
                }
            );
        }

        if (currentPage.pageId !== 7) {
            setTimeout(function () {
                commentVideoEmbed();
            }, 3000);
        }
    });

    function commentsAjaxCall(url, postid) {
        return $.ajax({
            method: "post",
            dataType: "html",
            data: { action: "get_lesson_comments", id: postid },
            url,
        });
    }

    function getVideoHTML(
        videoTitle,
        videoSrc,
        favoriteButton,
        fileElements,
        videoDesc,
        commentContent,
        videoPlayer,
        videoShareHTML
    ) {
        let html =
            '<div class="lesson_content_wrap">' +
            '<span id="close_video"></span>' +
            '<div class="lesson_title">' +
            "<h3>" +
            videoTitle +
            "</h3>" +
            "</div>" +
            '<div class="content_wrap">' +
            '<div class="video_iframe_wrap">';

        html += '<div class="video_wrapper">';

        html +=
            '<iframe frameborder="0" allowfullscreen src="' +
            videoSrc +
            '"></iframe>' +
            "</div>";

        html += '<div class="bottom_row">';

        if (currentPage.pageId != 7) {
            html += '<div class="button_wrap">' + favoriteButton + "</div>";
        }

        if (fileElements) {
            html += '<div class="links_wrap">' + fileElements + "</div>";
        }

        html += "</div>";
        if (currentPage.pageId == 7) {
            html += '</div><div class="video_content_wrap">';
        } else {
            html += videoDesc + '</div><div class="video_content_wrap">';
        }

        if (commentContent) {
            html +=
                '<div id="comments" class="comments-area">' +
                '<ol class="comment-list">' +
                commentContent +
                "</ol>" +
                "</div>";
        } else {
            html += videoShareHTML;
        }

        html += "</div>";

        if (currentPage.pageId == 7) {
            html += videoDesc + "</div></div>";
        } else {
            html += "</div></div>";
        }

        $(html).appendTo(videoPlayer);

        replyToComment($("a.comment-reply-link"));
        commentCancel();

        document.getElementById("close_video").addEventListener("click", () => {
            document.querySelector("#global_header").style.zIndex = 999;
            videoPlayer.removeClass("open");
            $("body, html").css("overflow-y", "auto");
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

    /* const bcountryDiv = jQuery('label[for="bcountry"]').closest("div");
    bcountryDiv.insertAfter(jQuery("#country_drop").closest("div")); */
    const countryDrop = document.getElementById("country_drop");

    if (countryDrop) {
        setTimeout(() => {
            document
                .getElementById("country_drop_target")
                .appendChild(countryDrop);
        }, 500);
    }

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

    const SwiperSlider = new Swiper(".swiper", {
        loop: true,
        slidesPerView: 4,
        spaceBetween: 30,
        zoom: true,
        autoplay: {
            delay: 2500,
            disableOnInteraction: false,
        },
        breakpoints: {
            // when window width is >= 550px
            200: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            // when window width is >= 768px
            768: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
            // when window width is >= 992px
            992: {
                slidesPerView: 4,
                spaceBetween: 40,
            },
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
    });

    const titleInput = document.querySelector("#acf-_post_title");

    if (titleInput) {
        const postTitleLabel = document.querySelector(
            ".acf-field--post-title .acf-label"
        );

        if (document.activeElement === titleInput) {
            postTitleLabel.classList.add("active");
        }
        titleInput.addEventListener("focus", () => {
            postTitleLabel.classList.add("active");
        });
        titleInput.addEventListener("blur", () => {
            if (titleInput.value === "") {
                postTitleLabel.classList.remove("active");
            }
        });

        if (titleInput.value !== "") {
            postTitleLabel.classList.add("active");
        }
    }

    /**
     * Determines if _ is lodash or not
     */
    const isLodash = () => {
        let isLodash = false;

        // If _ is defined and the function _.forEach exists then we know underscore OR lodash are in place
        if ("undefined" != typeof _ && "function" == typeof _.forEach) {
            // A small sample of some of the functions that exist in lodash but not underscore
            const funcs = ["get", "set", "at", "cloneDeep"];

            // Simplest if assume exists to start
            isLodash = true;

            funcs.forEach(function (func) {
                // If just one of the functions do not exist, then not lodash
                isLodash = "function" != typeof _[func] ? false : isLodash;
            });
        }

        if (isLodash) {
            // We know that lodash is loaded in the _ variable
            return true;
        } else {
            // We know that lodash is NOT loaded
            return false;
        }
    };

    /**
     * Address conflicts
     */
    if (isLodash()) {
        _.noConflict();
    }

    if (currentPage.pageName.includes("Profile")) {
        const text = document.getElementById("wpua-upload-messages-existing");
        const targetElement = document.getElementById(
            "wpua-upload-button-existing"
        );
        text.parentNode.removeChild(text);
        targetElement.appendChild(text);
    }

    const customFileUpload = document.querySelector("input[type='file']");

    if (customFileUpload) {
        customFileUpload.onchange = function () {
            const file = this.value.replace(/C:.*?fakepath\\/g, "");
            this.style.setProperty("--before-content", `"${file}"`);
            this.classList.add("file_selected");
        };
    }
});
