<!-- 404 Page Content -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="error-page">
                                <div class="d-flex align-items-center justify-content-center" style="gap:30px;flex-wrap:wrap;">
                                    <div style="flex:0 0 220px; text-align:center;">
                                        <div style="font-size:110px; font-weight:700; color:#f39c12; line-height:1;">404</div>
                                        <div class="text-muted" style="margin-top:8px;">Page Not Found</div>
                                    </div>

                                    <div style="flex:1 1 420px; max-width:720px; text-align:left;">
                                        <h3 class="mb-2"><i class="fas fa-university text-warning"></i> Whoops — we couldn't find that page</h3>
                                        <p class="text-muted">The page you requested either doesn't exist, has been moved, or you're not authorized to view it. If you believe this is an error, please contact support or use the quick links below.</p>

                                        <div class="mb-3">
                                            <a href="<?= site_url('admin/dashboard') ?>" class="btn btn-primary mr-2"><i class="fas fa-home mr-1"></i> Go to Dashboard</a>
                                            <a href="<?= site_url('admin/bank') ?>" class="btn btn-outline-primary mr-2"><i class="fas fa-file-import mr-1"></i> Bank Import</a>
                                            <a href="<?= site_url('admin/loans') ?>" class="btn btn-outline-primary mr-2"><i class="fas fa-hand-holding-usd mr-1"></i> Loans</a>
                                            <a href="javascript:history.back()" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Go Back</a>
                                        </div>

                                        <div class="card bg-light p-2" style="border-radius:6px;">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-3" style="font-size:20px;"><i class="fas fa-info-circle text-info"></i></div>
                                                <div>
                                                    <div style="font-weight:600;">Need help?</div>
                                                    <div class="text-muted small">Contact the administrator at <a href="mailto:admin@windeep.com">admin@windeep.com</a> or file a ticket from the support portal.</div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>