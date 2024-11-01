jQuery(document).ready(function () {


    function getCurrentURL() {
        return window.location.href
    }

    if (getCurrentURL().includes('wp-admin') && getCurrentURL().includes('syncio')) {

        jQuery("#btnGenerate").click(generateTable);

        let syncioPluginRestUrl = syncio_globals.rest_url;
        let syncioPluginSiteUrl = syncio_globals.site_url;
        let syncio_access_token = syncio_globals.syncio_access_token;
        let SyncioInstallerData = syncio_globals.syncio_installer_data;
        let syncio_img_dir_url = syncio_globals.img_dir_url;
        let installerCallbackUrl = syncio_globals.syncio_installer_url + '?';
        let syncioRedirect = syncio_globals.syncio_url + 'woo-login?token=';
        let syncioRetailerNonce = syncio_globals.syncio_retailer_nonce;


        let registerToWoocommerce = jQuery('#registerToWoocommerce');
        let registerToSyncio = jQuery('#registerToSyncio');
        let openSyncio = jQuery('#openSyncio');
        let container = jQuery('.container');

        let passed = false;


        function setSyncioLogoSrc() {
            jQuery("#syncio-logo").attr("src", syncio_img_dir_url + 'syncio_logo.svg');

        }

        function hasAccessToken() {
            return syncio_access_token;
        }


        jQuery("#registerToWoocommerceButton").click(function () {
            window.open(syncioPluginSiteUrl + '/wc-auth/v1/authorize?app_name=Syncio&scope=read_write&user_id=1&return_url=' + syncioPluginSiteUrl + '/wp-admin/admin.php?page=syncio&callback_url=' + encodeURIComponent(syncioPluginSiteUrl + '/wp-json/syncio/retailer/v1/callbackFromWoocommerce'))
        });

        var _nonce = "<?php echo wp_create_nonce( 'wp_rest_token' ); ?>";
        jQuery("#registerToSyncioButton").click(function () {

           syncioInstallerData['nonce'] =  syncio_globals.save_access_token_nonce;
            //alert(installerCallbackUrl + jQuery.param(dataToSyncioInstaller));
            // console.log(installerCallbackUrl + jQuery.param(syncioInstallerData))
            window.open(installerCallbackUrl + jQuery.param(syncioInstallerData));
        });


        jQuery("#openSyncioButton").click(function () {
            if (hasAccessToken())
                window.open(syncioRedirect + syncio_access_token)
            else
                swal("Failed!", "Something went wrong! = No token ", "warning");
        });

        jQuery("#refreshButton").click(function () {
            hideAllField();
            getRequirements();
            swal("Success!", "Health check completed", "success");

        });

        // jQuery("#uninstallEcomButton").click(function () {
        //     uninstallEcom();
        // });

        jQuery("#resetDataButton").click(function () {
            resetData();
        });


        function getWooValidation(){
            jQuery.ajax({
                url: syncioPluginRestUrl + 'wooValidation',
                type: "get",
                dataType: 'json',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', syncioRetailerNonce);
                },
                success: function (result) {
                    //console.log(result)
                    //console.log(swal.version);
                    var requirementsTable = jQuery("#woo-validation");
                    if(result === 'OK'){
                        requirementsTable.html(" WooCommerce keys are valid");
                        jQuery("#resetDataButton").hide();
                    }else{
                        requirementsTable.html(" WooCommerce keys are not valid | message = "+result);
                        jQuery("#resetDataButton").show();
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    var requirementsTable = jQuery("#woo-validation");
                    requirementsTable.html(" WooCommerce keys are not valid");
                    jQuery("#resetDataButton").show();

                }
            });
        }

        function getRequirements() {
            getDataForFrontend();

            jQuery.ajax({
                url: syncioPluginRestUrl + 'getRequirements',
                type: "get",
                dataType: 'json',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', syncioRetailerNonce);
                },
                success: function (result) {
                    checkRequirements(result.data)
                    // console.log(result.data)
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    // console.log(thrownError);
                    // swal({
                    //     title: "Something went wrong!",
                    //     text: "https://help.syncio.co/en/articles/5074038-how-to-install-syncio-to-your-wordpress-store-woocommerce-integration",
                    //     icon: "warning",
                    //     buttons: false,
                    //     dangerMode: true,
                    // });
                }
            });
        }


        function getDataForFrontend() {
            jQuery.ajax({
                url: syncioPluginRestUrl + 'getDataForFrontend',
                type: "get",
                dataType: 'json',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', syncioRetailerNonce);
                },
                success: function (result) {
                    console.log(result.data)
                    syncioInstallerData = result.data.syncio_installer_data;
                    syncioPluginRestUrl = result.data.rest_url;
                    syncioPluginSiteUrl = result.data.site_url;
                    syncio_access_token = result.data.syncio_access_token;

                    checkInstalledSyncio();

                }
            });
        }

        function resetData(){
            swal({
                title: "Are you sure?",
                text: "Once data is deleted, you will not be able to reach your Syncio account!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
                .then((willDelete) => {
                    if (willDelete) {
                        swal("Your syncio connection has been deleted!");
                        sendResetDataRequest();
                    }
                });
        }

        // function uninstallEcom() {
        //
        //     swal({
        //         title: "Are you sure?",
        //         text: "Once deleted, you will not be able to reach your Syncio account!",
        //         icon: "warning",
        //         buttons: true,
        //         dangerMode: true,
        //     })
        //         .then((willDelete) => {
        //             if (willDelete) {
        //                 swal("Your syncio  connection has been deleted!");
        //                 sendUninstallRequest();
        //             }
        //         });
        //
        // }

        function sendResetDataRequest(){
            jQuery.ajax({
                url: syncioPluginRestUrl + 'resetData',
                type: "post",
                dataType: 'json',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', syncioRetailerNonce);
                },
                success: function (result) {
                    // console.log(result);
                    swal("Success!", "Your Syncio local data  has been deleted! =="+result, "success");
                    getRequirements();
                },
                error: function (e) {
                    swal("Failed!", "Something went wrong! Maybe your account has already been deleted."+e, "warning");
                    // console.log(e);
                    getRequirements();
                }
            });
        }

        // function sendUninstallRequest() {
        //     jQuery.ajax({
        //         url: syncioPluginRestUrl + 'uninstallEcom',
        //         type: "post",
        //         dataType: 'json',
        //         beforeSend: function (xhr) {
        //             xhr.setRequestHeader('X-WP-Nonce', syncioRetailerNonce);
        //         },
        //         success: function (result) {
        //             console.log(result.data);
        //             swal("Success!", "Your Syncio account has been deleted!", "success");
        //             getRequirements();
        //         },
        //         error: function (e) {
        //             swal("Failed!", "Something went wrong! Maybe your account has already been deleted.", "warning");
        //             console.log(e);
        //             getRequirements();
        //         }
        //     });
        // }

        function checkRequirements(requirements) {
            let checkedRequirements = 0;
            let requirementsLength = 0;
            jQuery.each(requirements, function (key, value) {
                if (value.pass)
                    checkedRequirements++;
                requirementsLength++;

            });

            passed = checkedRequirements === requirementsLength;
            // console.log(passed);

            if (!passed) {
                generateTable(requirements);
                // hideAllField();
            } else {
                // hideRequirementsTable();
                generateTable(requirements);
            }

            showContainer();
        }

        function hideRequirementsTable() {
            // jQuery('#requirementsTable').css("display", "none");
        }

        function checkInstalledSyncio() {

            //hideAllField();
            // showOpenSyncio(); // temp
            if (passed)
                if (syncio_access_token) {
                    showOpenSyncio();
                } else if (syncioInstallerData) {
                    showRegisterToSyncio();
                } else {
                    showRegisterToWoocommerce();
                }

        }

        function hideAllField() {
            hideOpenSyncio();
            hideRegisterToSyncio();
            hideRegisterToWoocommerce();
        }


        function hideOpenSyncio() {
            openSyncio.css("display", "none");
        }

        function showOpenSyncio() {
            openSyncio.css("display", "inline");
        }

        function hideRegisterToSyncio() {
            registerToSyncio.css("display", "none");
        }

        function showRegisterToSyncio() {
            registerToSyncio.css("display", "inline");
        }

        function hideRegisterToWoocommerce() {
            registerToWoocommerce.css("display", "none");
        }

        function showRegisterToWoocommerce() {
            registerToWoocommerce.css("display", "inline");
        }

        function hideContainer() {
            container.css("display", "none");
        }

        function showContainer() {
            container.css("display", "inline");
        }


        function generateTable(requirementsData) {
            var statusTable = [];
            statusTable.push(["Component", "Status", "How to fix"]);
            var passIcon = `<svg style="width: 24px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-check">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>`;

            var failIcon = `<svg style="width: 24px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-cross">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>`;

            jQuery.each(requirementsData, function (key, value) {
                statusTable.push([value.title, value.pass ? '<span style="color: green">' + passIcon + '</span>' : '<span style="color:red;">' + failIcon + '</span>', value.pass ? '/' : value.solution])
            });

            //Create a HTML conatiner element.
            var container = jQuery("<div class='syncio-container' /> <h2>Requirements check</h2>");

            // Get the count of columns.
            var columnCount = statusTable[0].length;

            // Add the header row.
            var headerRow = jQuery("<div class='syncio-row syncio-header' />");
            for (var i = 0; i < columnCount; i++) {
                var headerCell = jQuery("<div class='syncio-cell syncio-header-cell' />");
                headerCell.html(statusTable[0][i]);
                headerRow.append(headerCell);
            }
            container.append(headerRow);

            // Add the data rows.
            for (var i = 1; i < statusTable.length; i++) {
                var row = jQuery("<div class='syncio-row' />");
                for (var j = 0; j < columnCount; j++) {
                    var cell = jQuery("<div class='syncio-cell' />");
                    cell.html(statusTable[i][j]);
                    row.append(cell);
                }
                container.append(row);
            }

            // Clear the existing content and append the new container.
            var requirementsTable = jQuery("#requirementsTable");
            requirementsTable.html("");
            requirementsTable.append(container);
        }


        setSyncioLogoSrc();

        hideContainer();

        getRequirements();

        hideAllField();

        getDataForFrontend();

        getWooValidation();
    }

});
