<?php

use yii\helpers\Html;
use app\widgets\Alert;

$this->title = 'Verify Password';
?>
<!-- auth-page content -->
<div class="auth-page-content overflow-hidden pt-lg-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card overflow-hidden galaxy-border-none card-bg-fill">
                    <div class="row g-0">
                        <div class="col-lg-6">
                            <div class="p-lg-5 p-4 auth-one-bg h-100">
                                <div class="bg-overlay"></div>
                                <div class="position-relative h-100 d-flex flex-column">
                                    <div class="mb-4">
                                        <a href="/" class="d-block">
                                            <img src="/images/logo-light.png" alt="<?= Html::encode(Yii::$app->name); ?>">
                                        </a>
                                    </div>
                                    <div class="mt-auto">
                                        <div class="mb-3">
                                            <h2 class="text-success">Account Recovery</h2>
                                            <p class="text-white">To help keep your account safe, Google wants to make sure that it’s really you trying to sign in</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->

                        <div class="col-lg-6">
                            <div class="p-lg-5 p-4">
                                <div>
                                    <h5 class="text-primary">Forgot Password?</h5>
                                    <p class="text-muted">Reset password with <?= Html::encode(Yii::$app->name); ?></p>
                                </div>

                                <div class="mt-2 text-center">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/rhvddzym.json" trigger="loop" colors="primary:#0ab39c" class="avatar-xl">
                                    </lord-icon>
                                </div>

                                <?php echo Alert::widget(); ?>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                    <!-- end row -->
                </div>
                <!-- end card -->
            </div>
            <!-- end col -->
        </div>
        <!-- end row -->
    </div>
    <!-- end container -->
</div>
<!-- end auth page content -->
