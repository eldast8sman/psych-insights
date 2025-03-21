<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\ChatGPTController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AudioController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\PodcastController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DailyTipController;
use App\Http\Controllers\Admin\InterestController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\DailyQuoteController;
use App\Http\Controllers\Admin\LearnAndDoController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\DassQuestionController;
use App\Http\Controllers\Admin\BasicQuestionController;
use App\Http\Controllers\Admin\DailyQuestionController;
use App\Http\Controllers\Admin\GoalController;
use App\Http\Controllers\Admin\ListenAndLearnController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ReadAndReflectController;
use App\Http\Controllers\Admin\SelfReflectionController;
use App\Http\Controllers\Admin\SubscriptionPackageController;
use App\Http\Controllers\BlogController as UserBlogController;
use App\Http\Controllers\Admin\PremiumCategoryScoreRangeController;
use App\Http\Controllers\AuthController as ControllersAuthController;
use App\Http\Controllers\BookController as ControllersBookController;
use App\Http\Controllers\AudioController as ControllersAudioController;
use App\Http\Controllers\VideoController as ControllersVideoController;
use App\Http\Controllers\ArticleController as ControllerArticleController;
use App\Http\Controllers\LearnAndDoController as UserLearnAndDoController;
use App\Http\Controllers\PodcastController as ControllersPodcastController;
use App\Http\Controllers\ListenAndLearnController as UserListenAndLearnController;
use App\Http\Controllers\ReadAndReflectController as UserReadAndReflectController;
use App\Http\Controllers\DassQuestionController as ControllerDassQuestionController;
use App\Http\Controllers\BasicQuestionController as ControllersBasicQuestionController;
use App\Http\Controllers\DailyQuestionController as ControllersDailyQuestionController;
use App\Http\Controllers\GoalController as ControllersGoalController;
use App\Http\Controllers\NotificationController as ControllersNotificationController;
use App\Http\Controllers\SelfReflectionController as ControllersSelfReflectionController;
use App\Http\Controllers\UserNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('cron-jobs')->group(function(){
    Route::controller(ControllersNotificationController::class)->group(function(){
        Route::get('/send-notifications', 'send')->name('cron.send_notification');
    });
});

Route::prefix('admin')->group(function(){
    Route::controller(AuthController::class)->group(function(){
        Route::post('/add-temp-admin', 'storeAdmin')->name('admin.addTempAdmin');
        Route::get('/by-token/{token}', 'byToken')->name('admin.byToken');
        Route::post('activate-account', 'activate_account')->name('admin.activateAccount');
        Route::post('/login', 'login')->name('admin.login');
        Route::post('/forgot-password', 'forgot_password')->name('admin.forgotPassword');
        Route::post('/reset-password', 'reset_password')->name('admin.resetPassword');
        Route::get('/add-notification-settings', 'add_notification_settings');
    });

    Route::middleware('auth:admin-api')->group(function(){
        Route::controller(AuthController::class)->group(function(){
            Route::get('/me', 'me')->name('admin.me');
            Route::post('/change-password', 'change_password')->name('admin.changePassword');
            Route::put('/me', 'update')->name('admin.updateProfile');
            Route::get('/dashboard', 'dashboard');
        });

        Route::controller(AdminController::class)->group(function(){
            Route::get('/admins', 'index')->name('admin.index');
            Route::post('/admins', 'store')->name('admin.store');
            Route::get('/admins/{admin}/resend-link', 'resend_activation_link')->name('admin.resendActivationLink');
            Route::get('/admins/{admin}', 'show')->name('admin.show');
            Route::put('/admins/{admin}', 'update')->name('admin.update');
            Route::get('/admins/{admin}/activation', 'account_activation')->name('admin.accountActivation');
            Route::delete('/admins/{admin}', 'destroy')->name('admin.delete');
        });

        Route::controller(CategoryController::class)->group(function(){
            Route::get('/categories', 'index')->name('admin.categories.index');
            Route::post('/categories', 'store')->name('admin.categories.store');
            Route::get('/categories/{category}', 'show')->name('admin.categories.show');
            Route::put('/categories/{category}', 'update')->name('admin.categories.update');
            Route::delete('/categories/{category}', 'destroy')->name('admin.categories.delete');
        });

        Route::controller(DailyQuestionController::class)->group(function(){
            Route::get('/total-checkins', 'total_checkins')->name('admin.dailyQuestions.totalCheckin');
            Route::get('/daily-questions', 'index')->name('admin.dailyQuestions.index');
            Route::post('/daily-questions', 'store')->name('admin.dailyQuestions.store');
            Route::get('/daily-questions/{question}', 'show')->name('admin.dailyQuestions.show');
            Route::put('/daily-questions/{question}', 'update')->name('admin.dailyQuestions.update');
            Route::post("/daily-questions/{question_id}/options", 'add_option')->name('admin.dailyQuestions.options.store');
            Route::delete('/daily-questions/options/{option}', 'remove_option')->name('admin.dailyQuestions.options.delete');
            Route::delete('/daily-questions/{question}', 'destroy')->name('admin.dailyQuestions.delete');
        });

        Route::controller(BasicQuestionController::class)->group(function(){
            Route::post('/basic-question-options', 'add_options')->name('admin.basicQuestionOption.store');
            Route::get('/basic-question-options', 'fetch_options')->name('admin.basicQuestionOption.index');
            Route::put('/basic-question-options', 'update_options')->name('admin.basicQuestionOption.update');
            Route::delete('/basic-question-options/{option}', 'remove_option')->name('admin.basicQuestionOption.delete');
            Route::get('/basic-questions', 'index')->name('admin.basicQuestion.index');
            Route::post('/basic-questions', 'store')->name('admin.basicQuestion.store');
            Route::get('/basic-questions/{question}', 'show')->name('admin.basicQuestion.show');
            Route::put('/basic-questions/{question}', 'update')->name('admin.basicQuestion.update');
            Route::put('/basic-questions/{question}/set-prerequisite', 'give_prerequisite')->name('admin.basicQuestion.setPrerequisite');
            Route::delete('/basic-question-special-options/{option}', 'delete_special_option')->name('admin.basicQuestionSpeciaOption.delete');
            Route::delete('/basic-questions/{question}', 'destroy')->name('admin.basicQuestion.delete');
            Route::post('/basic-question-score-ranges', 'store_score_range')->name('admin.basicQuestionScoreRange.store');
            Route::get('/basic-question-score-ranges', 'fetch_score_ranges')->name('admin.basicQuestionScoreRange.index');
            Route::get('/basic-question-score-ranges/{range}', 'show_score_range')->name('admin.basicQuestionScoreRange.show');
            Route::put('/basic-question-score-ranges/{range}', 'update_score_range')->name('admin.basicQuestionScoreRange.update');
            Route::delete('/basic-question-score-ranges/{range}', 'destroy_score_range')->name('admin.basicQuestionScoreRange.delete');
        });

        Route::controller(DassQuestionController::class)->group(function(){
            Route::post('/dass-question-options', 'add_options')->name('admin.dassQuestionOption.store');
            Route::get('/dass-question-options', 'fetch_options')->name('admin.dassQuestionOption.index');
            Route::put('/dass-question-options', 'update_options')->name('admin.dassQuestionOption.update');
            Route::delete('/dass-question-options/{option}', 'remove_option')->name('admin.dassQuestionOption.delete');
            Route::get('/dass-questions', 'index')->name('admin.dassQuestion.index');
            Route::post('/dass-questions', 'store')->name('admin.dassQuestion.store');
            Route::get('/dass-questions/{question}', 'show')->name('admin.dassQuestion.show');
            Route::put('/dass-questions/{question}', 'update')->name('admin.dassQuestion.update');
            Route::delete('/dass-questions/{question}', 'destroy')->name('admin.dassQuestion.delete');
        });

        Route::controller(SubscriptionPackageController::class)->group(function(){
            Route::get('/subscription-packages', 'index')->name('admin.subscriptionPackae.index');
            Route::post('/subscription-packages', 'store')->name('admin.subscriptionPackages.store');
            Route::get('/subscription-packages/{package}', 'show')->name('admin.subscriptionPackages.show');
            Route::put('/subscription-packages/{package}', 'update')->name('admin.subscriptionPackage.update');
            Route::delete('/subscription-packages/payment-plans/{plan}', 'destroy_payment_plan')->name('admin.subscriptionPackage.paymentPlan.delete');
            Route::delete('/subscription-packages/{package}', 'destroy')->name('admin.subscriptionPackage.delete');
            Route::post('/free-trials', 'add_free_package')->name('admin.freeTrial.add');
            Route::get('/free-trials', 'fetch_free_package')->name('admin.freeTrial.fetch');
            Route::delete('/free-trials', 'destroy_free_package')->name('admin.freeTrial.delete');
            Route::post('/free-package', 'add_basic_package')->name('admin.freePackage.upload');
            Route::get('/free-package', 'fetch_basic_package')->name('admin.freePackage.show');
            Route::get('/subscription-summary', 'summary')->name('admin.subscriptionSummary');
            Route::get('/subscribers', 'subscribers')->name('admin.subscriber');
        });

        Route::controller(PromoCodeController::class)->group(function(){
            Route::get('/promo-codes', 'index')->name('admin.promoCode.index');
            Route::post('/promo-codes', 'store')->name('admin.promoCode.store');
            Route::get('/promo-codes/{code}', 'show')->name('admin.promoCode.show');
            Route::put('/promo-codes/{code}', 'update')->name('admin.promoCode.update');
            Route::get('/promo-codes/{code}/activation', 'activation')->name('admin.promoCode.activation');
            Route::delete('/promo-codes/{code}', 'destroy')->name('admin.promoCode.delete');
        });

        Route::controller(BookController::class)->group(function(){
            Route::get('/books-summary', 'summary')->name('admin.book.summary');
            Route::get('/books', 'index')->name('admin.book.index');
            Route::post('/books', 'store')->name('admin.book.store');
            Route::get('/books/{book}', 'show')->name('admin.book.show');
            Route::post('/books/{book}', 'update')->name('admin.book.update');
            Route::get('/books/{book}/activation', 'activation')->name('admin.book.activation');
            Route::delete('/books/{book}', 'destroy')->name('admin.book.delete');
        });

        Route::controller(PodcastController::class)->group(function(){
            Route::get('/podcast-summary', 'summary')->name('admin.podcast.summary');
            Route::get('/podcasts', 'index')->name('admin.podcast.index');
            Route::post('/podcasts', 'store')->name('admin.podcast.store');
            Route::get('/podcasts/{podcast}', 'show')->name('admin.podcast.show');
            Route::post('/podcasts/{podcast}', 'update')->name('admin.podcast.update');
            Route::get('/books/{podcast}/activation', 'activation')->name('admin.podcast.activation');
            Route::delete('/podcasts/{podcast}', 'destroy')->name('admin.podcast.delete');
        });

        Route::controller(ArticleController::class)->group(function(){
            Route::get('/articles-summary', 'summary')->name('admin.article.summary');
            Route::get('/articles', 'index')->name('admin.article.index');
            Route::post('/articles', 'store')->name('admin.article.store');
            Route::get('/articles/{article}', 'show')->name('admin.article.show');
            Route::post('/articles/{music}', 'update')->name('admin.article.update');
            Route::get('/articles/{article}/activation', 'activation')->name('admin.article.activation');
            Route::delete('/articles/{article}', 'destroy')->name('admin.article.delete');
        });

        Route::controller(VideoController::class)->group(function(){
            Route::get('/video-summary', 'summary')->name('admin.video.summary');
            Route::get('/videos', 'index')->name('admin.video.index');
            Route::post('/videos', 'store')->name('admin.video.store');
            Route::get('/videos/{video}', 'show')->name('admin.video.show');
            Route::post('/videos/{video}', 'update')->name('admin.video.update');
            Route::get('/videos/{video}/activation', 'activation');
            Route::delete('/videos/{video}', 'destroy')->name('admin.video.delete');
        });

        Route::controller(AudioController::class)->group(function(){
            Route::get('/audio-summary', 'summary')->name('admin.audio.summary');
            Route::get('/audios', 'index')->name('admin.audio.index');
            Route::post('/audios', 'store')->name('admin.audio.store');
            Route::get('/audios/{audio}', 'show')->name('admin.audio.show');
            Route::post('/audios/{audio}', 'update')->name('admin.audio.update');
            Route::get('/audios/{audio}/activation', 'activation')->name('admin.audio.activation');
            Route::delete('/audios/{audio}', 'destroy')->name('admin.audio.delete');
        });

        Route::controller(PremiumCategoryScoreRangeController::class)->group(function(){
            Route::get('/premium-score-ranges', 'index')->name('admin.premiumScoreRange.index');
            Route::post('/premium-score-ranges', 'store')->name('admin.premiumScoreRange.store');
            Route::get('/premium-score-ranges/{range}', 'show')->name('admin.premiumScoreRange.show');
            Route::get('/premium-score-ranges/by-category/{category}', 'fetch_by_category');
            Route::put('/premium-score-ranges/{range}', 'update')->name('admin.premiumScoreRange.update');
            Route::delete('/premium-score-ranges/{range}', 'destroy')->name('admin.premiumScoreRange.delete');
        });

        Route::controller(UserController::class)->group(function(){
            Route::get('/user-summary', 'summary')->name('admin.user.summary');
            Route::get('/users', 'index')->name('admin.user.index');
            Route::get('/users/{user}', 'show')->name('admin.user.show');
            Route::get('/users/{user}/activation', 'user_activation')->name('admin.user.activation');
            Route::get('/user-countries', 'country_total')->name('admin.userCountries');
            Route::get('/closed-accounts', 'closed_accounts')->name('admin.closedAccounts');
        });

        Route::controller(InterestController::class)->group(function(){
            Route::get('/interests', 'index')->name('admin.interest.index');
            Route::post('/interests', 'store')->name('admin.interest.store');
            Route::get('/interests/{interest}', 'show')->name('admin.interest.show');
            Route::put('/interests/{interest}', 'update')->name('admin.interest.update');
            Route::delete('/interests/{interest}', 'destroy')->name('admin.interest.delete');
        });

        Route::controller(DailyQuoteController::class)->group(function(){
            Route::get('/daily-quotes', 'index')->name('admin.dailyQuote.index');
            Route::post('/daily-quotes', 'store')->name('admin.dailyQuote.store');
            Route::get('/daily-quotes/{quote}', 'show')->name('admin.dailyQuote.show');
            Route::put('/daily-quotes/{quote}', 'update')->name('admin.dailyQuote.update');
            Route::delete('/daily-quotes/{quote}', 'destroy')->name('admin.dailyQuote.delete');
            Route::get('/quotes-summary', 'quote_tip_summary')->name('admin.quoteTipSummary');
        });

        Route::controller(DailyTipController::class)->group(function(){
            Route::get('/daily-tips', 'index')->name('admin.dailyTip.index');
            Route::post('/daily-tips', 'store')->name('admin.dailyTip.store');
            Route::get('/daily-tips/{tip}', 'show')->name('admin.dailyTip.show');
            Route::put('/daily-tips/{tip}', 'update')->name('admin.dailyTip.update');
            Route::delete('/daily-tips/{tip}', 'destroy')->name('admin.dailyTip.delete');
        });

        Route::controller(ReadAndReflectController::class)->group(function(){
            Route::get('/read-and-reflect-summary', 'summary')->name('admin.readAndReflect.summary');
            Route::get('/read-and-reflects', 'index')->name('admin.readAndReflect.index');
            Route::post('/read-and-reflects', 'store')->name('admin.readAndReflect.store');
            Route::get('/read-and-reflects/{reflection}', 'show')->name('admin.readAndReflect.show');
            Route::post('/read-and-reflects/{reflection}/reflections', 'add_reflection')->name('admin.readAndReflect.Reflection.store');
            Route::put('/read-and-reflects/reflections/{reflection}', 'update_reflection')->name('admin.readAndReflect.reflection.update');
            Route::delete('/read-and-reflects/reflections/{reflection}', 'delete_reflection')->name('admin.readAndReflect.reflection.delete');
            Route::post('/read-and-reflects/{reflection}', 'update')->name('admin.readAndReflect.update');
            Route::get('/read-and-reflects/{read}/publish', 'publish')->name('admin.readAndReflect.publish');
            Route::delete('/read-and-reflects/{reflection}', 'destroy')->name('admin.readAndReflect.delete');
        });

        Route::controller(ListenAndLearnController::class)->group(function(){
            Route::get('/listen-and-learn-summary', 'summary')->name('admin.listenAndLearn.summary');
            Route::get('/listen-and-learns', 'index')->name('admin.listenAndLearn.index');
            Route::post('/listen-and-learns', 'store')->name('admin.listenAndLearn.store');
            Route::post('/listen-and-learns/{learn}/audios', 'add_audio')->name('admin.listenAndLearn.audio.store');
            Route::post('/listen-and-learns/audios/{audio}', 'update_audio')->name('admin.listenAndLearn.audio.update');
            Route::delete('/listen-and-learns/audios/{audio}', 'delete_audio')->name('admin.listenAndLearn.audio.delete');
            Route::get('/listen-and-learns/{learn}', 'show')->name('admin.listenAndLearn.show');
            Route::post('/listen-and-learns/{learn}', 'update')->name('admin.listenAndLearn.update');
            Route::get('/listen-and-learns/{learn}/publish', 'publish')->name('admin.listenAndLearn.publish');
            Route::delete('/listen-and-learns/{learn}', 'destroy')->name('admin.listenAndLearn.delete');
        });

        Route::controller(LearnAndDoController::class)->group(function(){
            Route::get('/learn-and-do-summary', 'summary')->name('admin.learnAndDo.summary');
            Route::get('/learn-and-dos', 'index')->name('admin.learnAndDo.index');
            Route::post('/learn-and-dos', 'store')->name('admin.learnAndDo.store');
            Route::get('/learn-and-dos/{learn}', 'show')->name('admin.learnAndDo.show');
            Route::get('/learn-and-dos/activities/{activity}', 'show_activity')->name('admin.learnAndDo.activity.show');
            Route::get('/learn-and-dos/questions/{question}', 'show_question')->name('admin.learnAndDo.question.show');
            Route::post('/learn-and-dos/{learn}/activities', 'store_activity')->name('admin.learnAndDo.activity.store');
            Route::post('/learn-and-dos/activities/{activity}/questions', 'store_question')->name('admin.learnAndDo.activity.question.store');
            Route::post('/learn-and-dos/{learn}', 'update')->name('admin.learnAndDo.update');
            Route::put('/learn-and-dos/activities/{activity}', 'update_activity')->name('admin.LearnAndDo.activity.update');
            Route::put('/learn-and-dos/questions/{question}', 'update_question')->name('admin.learnAndDo.question.update');
            Route::delete('/learn-and-dos/activities/{activity}', 'destroy_activity')->name('admin.learnAndD0.activity.delete');
            Route::delete('learn-and-dos/questions/{question}', 'destroy_question')->name('admin.learnAndDo.question.delete');
            Route::get('/learn-and-dos/{learn}/publish', 'publish')->name('admin.learnAndDo.publish');
            Route::delete('/learn-and-dos/{learn}', 'destroy')->name('admin.learnAndDo.delete');
        });

        Route::controller(SelfReflectionController::class)->group(function(){
            Route::post('/self-reflections', 'store')->name('admin.selfReflection.store');
            Route::get('/self-reflections', 'index')->name('admin.selfReflection.index');
            Route::get('/self-reflections/{category}', 'show')->name('admin.selfReflection.show');
            Route::post('/self-reflections/{category}/questions', 'store_question')->name('admin.selfReflection.question.store');
            Route::get('/self-reflections/questions/{question}', 'show_question')->name('admin.selfReflection.question.show');
            Route::post('/self-reflections/questions/{question}/options', 'store_option')->name('admin.selfReflection.question.option.store');
            Route::put('/self-reflections/options/{option}', 'update_option')->name('admin.selfReflection.option.update');
            Route::put('/self-reflections/questions/{question}', 'update_question')->name('admin.selfReflection.question.update');
            Route::put('/self-reflections/{category}', 'update')->name('admin.selfReflection.update');
            Route::get('/self-reflections/{category}/publish', 'publish')->name('admin.selfReflection.publish');
            Route::delete('/self-reflections/options/{option}', 'destroy_option')->name('admin.selfReflection.option.delete');
            Route::delete('/self-reflections/questions/{question}', 'destroy_question')->name('admin.selfReflection.question.delete');
            Route::delete('/self-reflections/{category}', 'destroy')->name('admin.selfReflection.delete');
        });

        Route::controller(BlogCategoryController::class)->group(function(){
            Route::get('/blog-categories', 'index')->name('admin.blogCategory.index');
            Route::post('/blog-categories', 'store')->name('admin.blogCategory.store');
            Route::get('/blog-categories/{category}', 'show')->name('admin.blogCategory.show');
            Route::put('/blog-categories/{category}', 'update')->name('admin.blogCategory.update');
            Route::delete('/blog-categories/{category}', 'destroy')->name('admin.blogCategory.delete');
        });

        Route::controller(BlogController::class)->group(function(){
            Route::get('/blogs', 'index')->name('admin.blog.index');
            Route::get('/blog-summary', 'summary')->name('summary');
            Route::post('/blogs', 'store')->name('admin.blog.store');
            Route::get('/blogs/{blog}', 'show')->name('admin.blog.show');
            Route::post('/blogs/{blog}', 'update')->name('admin.blog.update');
            Route::get('/blogs/{blog}/activation', 'activation')->name('admin.blog.activation');
            Route::delete('/blogs/{blog}', 'destroy')->name('admin.blog.delete');
        });

        Route::controller(GoalController::class)->group(function(){
            Route::get('/goals', 'index')->name('admin.goal.index');
            Route::post('/goals', 'store')->name('admin.goal.store');
            Route::post('/goals/{category}/reflections', 'store_reflections')->name('admin.goal.reflection.store');
            Route::post('/goals/{category}/questions', 'store_goal_questions')->name('admin.goal.question.store');
            Route::put('/goals/reflections/{reflection}', 'update_reflection')->name('admin.goal.reflection.update');
            Route::put('/goals/questions/{question}', 'update_goal_question')->name('admin.goal.question.update');
            Route::delete('/goals/reflections/{reflection}', 'destroy_reflection')->name('admin.goal.reflection.delete');
            Route::delete('/goals/questions/{question}', 'destroy_goal_question')->name('admin.goal.question.delete');
            Route::get('/goals/{category}', 'show')->name('admin.goal.show');
            Route::put('/goals/{category}', 'update')->name('admin.goal.update');
            Route::get('/goals/{category}/publish', 'publish')->name('admin.goal.publish');
            Route::delete('/goals/{category}', 'destroy')->name('admin.goal.delete');
        });

        Route::controller(NotificationController::class)->group(function(){
            Route::get('/notification-settings', 'fetch_setting')->name('admin.motificationSetting.fetch');
            Route::put('/notification-settings', 'update_setting')->name('admin.notificationSetting.update');
            Route::get('/notification-count', 'notification_count')->name('admin.notificationCCount');
            Route::get('/notifications', 'index')->name('admin.notification.index')->name('admin.notification.index');
            Route::get('/notifications/{notification}/mark-as-read', 'mark_as_read')->name('admin.notification.maekAsRead');
            Route::get('/notifications/mark-all-as-read', 'mark_all_as_read')->name('admin.notification.markAllAsRead');
            Route::get('/notifications/{notification}/cancel', 'destroy')->name('admin.notification.cancel');
        });
    });
});

Route::controller(ControllersAuthController::class)->group(function(){
    Route::post('/signup', 'store')->name('signup');
    Route::post('/verify-email', 'verify_email')->name('verifyEmail.verify');
    Route::post('/login', 'login')->name('login');
    Route::post('/forgot-password', 'forgot_password')->name('forgot_password');
    Route::post('/reset-password', 'reset_password')->name('reset_password');
    Route::get('/initiate-google-login', 'initiate_google_login')->name('initiate_google_login');
    Route::post('/google-login', 'google_login')->name('google_login');
    Route::post('/contact-us-mail', 'contact_us')->name('contactUsMail');
});

Route::middleware('auth:user-api')->group(function(){
    Route::controller(ControllersAuthController::class)->group(function(){    
        Route::get('/me', 'me')->name('me');    
        Route::get('/resend-email-verification-token', 'resend_email_verification_link')->name('veryfyEmail.resend');
        Route::post('/change-password', 'change_password')->name('changePassword');
        Route::post('/change-name', 'change_name')->name('changeName');
        Route::post('/change-email', 'change_email')->name('changeEmail');
        Route::post('/upload-profile-photo', 'upload_profile_photo')->name('uploadProfilePhoto');
        Route::get('/user-guide', 'user_guide')->name('userGuide');
        Route::post('/deactivate', 'deactivate')->name('userDeactivation');
    });

    Route::controller(ControllersBasicQuestionController::class)->group(function(){
        Route::get('/basic-questions', 'fetch_questions')->name('basicQuestion.fetch');
        Route::post('/basic-questions/answer', 'answer_basic_question')->name('basicQuestion.answer');
        Route::get('/interests', 'fetch_interests')->name('interest.fetch');
        Route::post('/interests', 'set_interests')->name('interest.set');
        Route::post('/basic-questions/temp-answers', 'answer_temp')->name('basicQuestion.tempAnswer.store');
        Route::get('/basic-questions/temp-answers', 'fetch_basic_temp_answer')->name('basicQuestion.tempAnswer.fetch');
    });

    Route::controller(ControllerDassQuestionController::class)->group(function(){
        Route::get('/dass-questions', 'fetch_questions')->name('dassQuestion.fetch');
        Route::post('/dass-questions/answer', 'answer_dass_questions')->name('dassQuestion.answer');
        Route::get('/distress-scores', 'distress_scores')->name('distressScore');
        Route::post('/dass-questions/temp-answers', 'answer_temp')->name('dassQuestion.tempAnswer.store');
        Route::get('/dass-questions/temp-answers', 'fetch_dass_temp_answer')->name('dassQuestion.tempAnswer.fetch');
    });

    Route::controller(ControllersDailyQuestionController::class)->group(function(){
        Route::get('/daily-questions', 'fetch_questions')->name('dailyQuestion.fetch');
        Route::post('/daily-questions/answer', 'answer_questions')->name('dailyQuestion.answer');
    });

    Route::controller(ControllersVideoController::class)->group(function(){
        Route::get('/recommended-videos', 'recommended_videos')->name('recommendedVideo.fetch');
        Route::get('/recommended-videos/{slug}', 'recommended_video')->name('recommendedVideo.show');
        Route::get('/videos/{slug}/mark-as-opened', 'mark_as_opened')->name('video.markAsOpened');
        Route::get('/opened-videos', 'opened_videos')->name('openedVideo.fetch');
        Route::get('/opened-videos/{slug}', 'opened_video')->name('openedVideo.show');
        Route::get('/videos/{slug}/favourite', 'video_favourite')->name('videoFavourite.addOrRemove');
        Route::get('/favourite-videos', 'favourite_videos')->name('favouriteVideo.fetch');
        Route::get('/favourite-videos/{slug}', 'favourite_video')->name('favouriteVideo.show');
    });

    Route::controller(ControllersAudioController::class)->group(function(){
        Route::get('/recommended-audios', 'recommended_audios')->name('recommendedAudio.fetch');
        Route::get('/recommended-audios/{slug}', 'recommended_audio')->name('recommendedAudio.show');
        Route::get('/audios/{slug}/mark-as-opened', 'mark_as_opened')->name('audio.markAsOpened');
        Route::get('/opened-audios', 'opened_audios')->name('openedAudio.fetch');
        Route::get('/opened-audios/{slug}', 'opened_audio')->name('openedAudio.show');
        Route::get('/audios/{slug}/favourite', 'audio_favourite')->name('audioFavourite.addOrRemove');
        Route::get('/favourite-audios', 'favourite_audios')->name('favouriteAudio.fetch');
        Route::get('/favourite-audios/{slug}', 'favourite_audio')->name('favouriteAudio.show');
    });

    Route::controller(ControllerArticleController::class)->group(function(){
        Route::get('/recommended-articles', 'recommended_articles')->name('recommendedArticle.fetch');
        Route::get('/recommended-articles/{slug}', 'recommended_article')->name('recommendedArticle.show');
        Route::get('/articles/{slug}/mark-as-opened', 'mark_as_opened')->name('article.markAsOpened');
        Route::get('/opened-articles', 'opened_articles')->name('openedArticle.fetch');
        Route::get('/opened-articles/{slug}', 'opened_article')->name('openedArticle.show');
        Route::get('/articles/{slug}/favourite', 'article_favourite')->name('articleFavourite.addOrRemove');
        Route::get('/favourite-articles', 'favourite_articles')->name('favouriteArticle.fetch');
        Route::get('/favourite-articles/{slug}', 'favourite_article')->name('favouriteArticle.show');
    });

    Route::controller(ControllersBookController::class)->group(function(){
        Route::get('/recommended-books', 'recommended_books')->name('recommendedBook.fetch');
        Route::get('/recommended-books/{slug}', 'recommended_book')->name('recommendedBook.show');
        Route::get('/books/{slug}/mark-as-opened', 'mark_as_opened')->name('book.markAsOpened');
        Route::get('/opened-books', 'opened_books')->name('openedBook.fetch');
        Route::get('/opened-books/{slug}', 'opened_book')->name('openedBook.show');
        Route::get('/books/{slug}/favourite', 'book_favourite')->name('bookFavourite.addOrRemove');
        Route::get('/favourite-books', 'favourite_books')->name('favouriteBook.fetch');
        Route::get('/favourite-books/{slug}', 'favourite_book')->name('favouriteBook.show');
    });

    Route::controller(ControllersPodcastController::class)->group(function(){
        Route::get('/recommended-podcasts', 'recommended_podcasts')->name('recommendedPodcast.fetch');
        Route::get('/recommended-podcasts/{slug}', 'recommended_podcast')->name('recommendedPodcast.show');
        Route::get('/podcasts/{slug}/mark-as-opened', 'mark_as_opened')->name('podcast.markAsOpened');
        Route::get('/opened-podcasts', 'opened_podcasts')->name('openedPodcast.fetch');
        Route::get('/opened-podcasts/{slug}', 'opened_podcast')->name('openedPodcast.show');
        Route::get('/podcasts/{slug}/favourite', 'podcast_favourite')->name('podcastFavourite.addOrRemove');
        Route::get('/favourite-podcasts', 'favourite_podcasts')->name('favouritePodcast.fetch');
        Route::get('/favourite-podcasts/{slug}', 'favourite_podcast')->name('favouritePodcast.show');
    });

    Route::controller(SubscriptionController::class)->group(function(){
        Route::get('/subscription-packages', 'subscription_packages')->name('subscriptionPackage.index');
        Route::get('/promo-codes/{promo_code}', 'fetch_promo_code')->name('promoCode.show');
        Route::post('/subscriptions/calculate-amount', 'fetch_calculated_amount')->name('subscription.calculateAmount');
        Route::post('/subscriptions/initiate', 'initiate_subscription')->name('subscription.initiate');
        Route::post('/subscriptions/complete', 'complete_subscription')->name('subscription.complete');
        Route::get('/subscription-attempts', 'subscription_attempts')->name('subscriptionAttempts.index');
        Route::get('/subscription-attempts/{internal_ref}', 'subscription_attempt')->name('subscriptionAttempt.show');
        Route::get('/payment-methods', 'fetch_user_payment_methods')->name('paymentMethods');
        Route::delete('/payment-methods/{method}', 'remove_payment_method');
        Route::get('/payment-methods/{method}/charge', 'charge_previous_card')->name('paymentMethod.charge');
        Route::post('/subscriptions/initiate/old-card', 'initiate_subscription_old_card')->name('subscription.complete.oldCard');
        Route::post('/subscriptions/initiate-renewal', 'initiate_subscription_renewal')->name('subscription.initiateRenewal');
        Route::post('/subscriptions/initiate-renewal/old-card', 'initiate_subscription_renewal_old_card')->name('subscriptionRenewal.complete.oldCard');
        Route::get('/subscriptions/initiate/apple-pay/{payment_plan}', 'initiate_subscription_apple_pay')->name('subscripton.initiate.applePay');
        Route::get('/subscriptions/initiate-renewal/apple-pay/{payment_plan}', 'initiate_subscription_renewal_apple_pay')->name('subscription.initiateRenewal.applePay');
        Route::post('/subscriptions/complete/apple-pay', 'complete_subscription_apple_pay')->name('subscription.complete.applePay');
        Route::get('/subscription-auto-renew', 'auto_renew')->name('subscriptionAutoRenew');
    });

    Route::controller(JournalController::class)->group(function(){
        Route::get('/journals', 'index')->name('journal.index');
        Route::get('/pinned-journals', 'pinned_journals')->name('journal.pinned');
        Route::post('/journals', 'store')->name('journal.store');
        Route::get('/journals/{journal}', 'show')->name('journal.show');
        Route::put('/journals/{journal}', 'update')->name('journal.update');
        Route::get('/journals/{journal}/pin', 'pin_journal')->name('journal.pin');
        Route::delete('/journals/{journal}', 'destroy')->name('journal.delete');
    });

    Route::controller(UserListenAndLearnController::class)->group(function(){
        Route::get('/recommended-listen-and-learns', 'recommended_strategies')->name('recommendedListenAndLearn.fetch');
        Route::get('/recommended-listen-and-learns/{slug}', 'recommended_strategy')->name('recommendedListenAndLearn.show');
        Route::get('/listen-and-learns/{slug}/mark-as-opened', 'mark_as_opened')->name('recommendedListenAndLearn.markAsOpened');
        Route::get('/opened-listen-and-learns', 'opened_strategies')->name('openedListenAndLearn.fetch');
        Route::get('/opened-listen-and-learns/{slug}', 'opened_strategy')->name('openedListenAndLearn.show');
        Route::get('/listen-and-learns/{slug}/favourites', 'strategy_favourite')->name('listenAndLearn.addOrRemove');
        Route::get('/favourite-listen-and-learns', 'favourite_strategies')->name('favouriteListenAndLearn.fetch');
        Route::get('/favourite-listen-and-learns/{slug}', 'favourite_strategy')->name('favouriteListenAndLearn.show');
    });

    Route::controller(UserReadAndReflectController::class)->group(function(){
        Route::get('/recommended-read-and-reflects', 'recommended_strategies')->name('recommendedReadAndReflect.fetch');
        Route::get('/recommended-read-and-reflects/{slug}', 'recommended_strategy')->name('recommendedReadAndReflect.show');
        Route::get('/read-and-reflects/{slug}/mark-as-opened', 'mark_as_opened')->name('recommendedReadAndReflect.markAsOpened');
        Route::get('/opened-read-and-reflects', 'opened_strategies')->name('openedReadAndReflect.index');
        Route::get('/opened-read-and-reflects/{slug}', 'opened_strategy')->name('openedReadAndReflect.show');
        Route::get('/read-and-reflects/{slug}/favourites', 'strategy_favourite')->name('readAndReflect.addOrRemove');
        Route::get('/favourite-read-and-reflects', 'favourite_strategies')->name('favouriteReadAndReflect.index');
        Route::get('/favourite-read-and-reflects/{slug}', 'favourite_strategy')->name('favouriteReadAndReflect.show');
        Route::post('/read-and-reflects/{slug}/answer', 'answer_reflections')->name('readAndReflect.answer');
        Route::get('/read-and-reflects/{slug}/previous-answers', 'previous_answers')->name('readAndReflect.previousAnswer');
    });

    Route::controller(UserLearnAndDoController::class)->group(function(){
        Route::get('/recommended-learn-and-dos', 'recommended_strategies')->name('recommendedLearnAndDo.fetch');
        Route::get('/recommended-learn-and-dos/{slug}', 'recommended_strategy')->name('recommendedLearnAndDo.show');
        Route::get('/learn-and-dos/{slug}/mark-as-opened', 'mark_as_opened')->name('recommendedLearnAndDo.markAsOpened');
        Route::get('/opened-learn-and-dos', 'opened_strategies')->name('openedLearnAndDo.index');
        Route::get('/opened-learn-and-dos/{slug}', 'opened_strategy')->name('openedLearnAndDo.show');
        Route::get('/learn-and-dos/{slug}/favourites', 'strategy_favourite')->name('learnAndDo.addOrRemove');
        Route::get('/favourite-learn-and-dos', 'favourite_strategies')->name('favouriteLearnAndDo.index');
        Route::get('/favourite-learn-and-dos/{slug}', 'favourite_strategy')->name('favouriteReadAndReflect.show');
        Route::post('/learn-and-dos/{slug}/answer', 'answer_questions')->name('learnAndDo.answer');
        Route::get('/learn-and-dos/{slug}/previous-answers', 'previous_answers')->name('learnAndDo.previousAnswer');
    });

    Route::controller(ControllersSelfReflectionController::class)->group(function(){
        Route::get('/self-reflections', 'index')->name('selfReflection.index');
        Route::get('/self-reflections/{slug}', 'show')->name('selfReflection.show');
        Route::post('/self-reflections/{slug}/answer', 'answer_reflection')->name('selfReflection.answer');
        Route::get('/self-reflection-answers', 'previous_answers')->name('selfReflection.answers');
    });

    Route::controller(UserBlogController::class)->group(function(){
        Route::get('/blog-categories', 'categories')->name('blogCategory.index');
        Route::get('/blogs', 'index')->name('blog.index');
        Route::get('/blogs/by-category/{slug}', 'byCategory')->name('blog.byCategory');
        Route::get('/blogs/{slug}', 'show')->name('blog.show');
        Route::get('/blog/{slug}/favourites', 'blog_favourite')->name('blog.favourite');
        Route::get('/favourite-blogs', 'favourite_blogs')->name('faouriteBlog.index');
    });

    Route::controller(DashboardController::class)->group(function(){
        Route::get('/dashboard/activities', 'activities')->name('dashboard.actiity');
        Route::get('/dashboard/my-stats', 'my_stat')->name('dashboard.myStat');
        Route::get('/dashboard/my-progress', 'my_progress');
        Route::get('/dashboard/days-in-a-row', 'days_in_a_row')->name('dashboard.daysInARow');
        Route::get('/dashboard/milestones', 'milestones')->name('dashboard.milestones');
    });

    Route::controller(ControllersGoalController::class)->group(function(){
        Route::get('/goals', 'index')->name('goal.index');
        Route::get('/goals/{slug}', 'show')->name('goal.shw');
        Route::post('/goals/{slug}/answer-reflections', 'answer_reflection')->name('goal.answer_reflections');
        Route::post('/goals/{slug}/set-goals', 'set_goals')->name('goal.set_goals');
        Route::get('/previous-reflections', 'previous_reflections')->name('previousReflection.index');
        Route::get('/previous-goals', 'previous_goals')->name('previousGoal.index');
        Route::get('/all-goal-reminders', 'all_reminders')->name('allGoalReminder.index');
        Route::get('goal-reminders/{reminder}/cancel', 'cancel_reminder')->name('cancelGoalReminder');
    });

    Route::controller(UserNotificationController::class)->group(function(){
        Route::post('/notifications', 'store');
        Route::get('/notifications', 'index')->name('notification.index');
        Route::get('/notifications/count', 'notification_count')->name('notification.count');
        Route::get('/notifications/{notification}/mark-as-read', 'mark_as_read')->name('notification.markAsRead');
        Route::get('/notifications/mark-all-as-read', 'mark_all_as_read')->name('mark_all_as_read');
        Route::get('/notifications/{notification}/cancel', 'cancel')->name('notification.cancel');
        Route::get('/notifications/test', 'test_notification')->name('notification.test');
        Route::get('/notifications/settings', 'fetch_setting')->name('notification.setting.show');
        Route::post('/notifications/settings', 'update_setting')->name('notification.setting.update');
    });
});

Route::get('/test-token/{card}/{year}/{month}/{cvv}', [StripeController::class, 'create_token']);
Route::get('/test-customer/{name}/{email}', [StripeController::class, 'create_customer']);

Route::get('/test-gpt/{prompt}', [ChatGPTController::class, 'complete_chat']);
Route::post('/test-gpt-chat', [ChatGPTController::class, 'chat']);

Route::get('/test-ip/{ip_address}', [ControllersAuthController::class, 'test_ip']);

Route::post('/subscription/apple-pay/server-notification/{type}', [SubscriptionController::class, 'applepay_notification']);

Route::get('/test-payload', [App\Http\Controllers\NotificationController::class, 'test_payload']);