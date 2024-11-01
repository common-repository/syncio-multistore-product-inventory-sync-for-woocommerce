<?php ?>

<html lang="en">
<header></header>
<body class="syncio-body">
<div class="syncio-logo-container">
    <img src="" alt="syncio logo" class="syncio-logo" id="syncio-logo">
</div>

<div class="container">
    <div class="flex-container justify-content-netween">
        <div class="flex-col col-6">
            <div class="syncio-container">
                <h2>Syncio - Multistore Inventory Sync</h2>
                <div id="registerToWoocommerce" class="section">
                    <h3>Give Syncio permissions - step 1 of 3 </h3>
                    <p>Approve permissions so we can sync your data.</p>
                    <button id="registerToWoocommerceButton" class="syncio-button syncio-button-secondary">Approve permissions</button>
                </div>

                <div id="registerToSyncio" class="section">
                    <h3>Register with Syncio- step 2 of 3 </h3>
                    <p>Register to set up your Syncio app.</p>
                    <p id="woo-validation" style="font-weight: 600;"></p>
                    <button id="registerToSyncioButton" class="syncio-button syncio-button-secondary">Register to Syncio</button>
<!--                    <button id="resetDataButton" class="syncio-button syncio-button-warning"> Reset data for woo keys</button>-->
                </div>

                <div id="openSyncio" class="section">
                    <p>Your store has been successfully connected to your Syncio account.</p>
                    <button id="openSyncioButton" class="syncio-button syncio-button-secondary">Go to Syncio</button>
<!--                    <button id="uninstallEcomButton" class="syncio-button syncio-button-warning">Disconnect from Syncio</button>-->
                </div>
                <div id="refresh" class="section">
                    <button id="refreshButton" class="btn button-secondary">Run check</button>
                    <button id="resetDataButton" class="syncio-button syncio-button-warning"> Reset data for woo keys</button>
                </div>

                <div id="support-team" class="section">
                    <p style="margin-top: 0; font-size: 14px;">If you have any questions or need assistance, contact the Syncio team at support@syncio.co</p>
                    <a target="_blank" href="https://help.syncio.co/en/articles/9754862-how-to-install-syncio-to-your-wordpress-store-woocommerce-integration#h_50703c7f74n" class="syncio-link">Integration</a>
                    <a target="_blank" href="https://help.syncio.co/en/articles/9754862-how-to-install-syncio-to-your-wordpress-store-woocommerce-integration#h_8981598c96" class="syncio-link">Requirements</a>
                </div>
            </div>
        </div>

        <div class="flex-col col-6" id="requirementsTable"> </div>
    </div>
</div>
<?php wp_footer(); ?>
</body>
</html>
