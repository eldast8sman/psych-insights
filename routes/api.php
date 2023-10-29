<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\ChatGPTController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\Admin\AuthController;
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
use App\Http\Controllers\Admin\DassQuestionController;
use App\Http\Controllers\Admin\BasicQuestionController;
use App\Http\Controllers\Admin\DailyQuestionController;
use App\Http\Controllers\Admin\ListenAndLearnController;
use App\Http\Controllers\Admin\ReadAndReflectController;
use App\Http\Controllers\Admin\SubscriptionPackageController;
use App\Http\Controllers\Admin\PremiumCategoryScoreRangeController;
use App\Http\Controllers\AuthController as ControllersAuthController;
use App\Http\Controllers\BookController as ControllersBookController;
use App\Http\Controllers\AudioController as ControllersAudioController;
use App\Http\Controllers\VideoController as ControllersVideoController;
use App\Http\Controllers\ArticleController as ControllerArticleController;
use App\Http\Controllers\PodcastController as ControllersPodcastController;
use App\Http\Controllers\DassQuestionController as ControllerDassQuestionController;
use App\Http\Controllers\BasicQuestionController as ControllersBasicQuestionController;
use App\Http\Controllers\DailyQuestionController as ControllersDailyQuestionController;

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

Route::prefix('admin')->group(function(){
    Route::controller(AuthController::class)->group(function(){
        Route::post('/add-temp-admin', 'storeAdmin')->name('admin.addTempAdmin');
        Route::get('/by-token/{token}', 'byToken')->name('admin.byToken');
        Route::post('activate-account', 'activate_account')->name('admin.activateAccount');
        Route::post('/login', 'login')->name('admin.login');
        Route::post('/forgot-password', 'forgot_password')->name('admin.forgotPassword');
        Route::post('/reset-password', 'reset_password')->name('admin.resetPassword');
    });



    Route::middleware('auth:admin-api')->group(function(){
        Route::controller(AuthController::class)->group(function(){
            Route::get('/me', 'me')->name('admin.me');
            Route::post('/change-password', 'change_password')->name('admin.changePassword');
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
        });

        Route::controller(DailyTipController::class)->group(function(){
            Route::get('/daily-tips', 'index')->name('admin.dailyTip.index');
            Route::post('/daily-tips', 'store')->name('admin.dailyTip.store');
            Route::get('/daily-tips/{tip}', 'show')->name('admin.dailyTip.show');
            Route::put('/daily-tips/{tip}', 'update')->name('admin.dailyTip.update');
            Route::delete('/daily-tips/{tip}', 'destroy')->name('admin.dailyTip.delete');
        });

        Route::controller(ReadAndReflectController::class)->group(function(){
            Route::get('/read-and-reflects', 'index')->name('admin.readAndReflect.index');
            Route::post('/read-and-reflects', 'store')->name('admin.readAndReflect.store');
            Route::get('/read-and-reflects/{reflection}', 'show')->name('admin.readAndReflect.show');
            Route::post('/read-and-reflects/{reflection}/reflections', 'add_reflection')->name('admin.readAndReflect.Reflection.store');
            Route::put('/read-and-reflects/reflections/{reflection}', 'update_reflection')->name('admin.readAndReflect.reflection.update');
            Route::delete('/read-and-reflects/reflections/{reflection}', 'delete_reflection')->name('admin.readAndReflect.reflection.delete');
            Route::post('/read-and-reflects/{reflection}', 'update')->name('admin.readAndReflect.update');
            Route::delete('/read-and-reflects/{reflection}', 'destroy')->name('admin.readAndReflect.delete');
        });

        Route::controller(ListenAndLearnController::class)->group(function(){
            Route::get('/listen-and-learns', 'index')->name('admin.listenAndLearn.index');
            Route::post('/listen-and-learns', 'store')->name('admin.listenAndLearn.store');
            Route::post('/listen-and-learns/{learn}/audios', 'add_audio')->name('admin.listenAndLearn.audio.store');
            Route::post('/listen-and-learns/audios/{audio}', 'update_audio')->name('admin.listenAndLearn.audio.update');
            Route::delete('/listen-and-learns/audios/{audio}', 'delete_audio')->name('admin.listenAndLearn.audio.delete');
            Route::get('/listen-and-learns/{learn}', 'show')->name('admin.listenAndLearn.show');
            Route::post('/listen-and-learns/{learn}', 'update')->name('admin.listenAndLearn.update');
            Route::delete('/listen-and-learns/{learn}', 'destroy')->name('admin.listenAndLearn.delete');
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
});

Route::middleware('auth:user-api')->group(function(){
    Route::controller(ControllersAuthController::class)->group(function(){    
        Route::get('/me', 'me')->name('me');    
        Route::get('/resend-email-verification-token', 'resend_email_verification_link')->name('veryfyEmail.resend');
        Route::post('/change-password', 'change_password')->name('changePassword');
        Route::post('/change-name', 'change_name')->name('changeName');
        Route::post('/change-email', 'change_email')->name('changeEmail');
        Route::post('/upload-profile-photo', 'upload_profile_photo')->name('uploadProfilePhoto');
    });

    Route::controller(ControllersBasicQuestionController::class)->group(function(){
        Route::get('/basic-questions', 'fetch_questions')->name('basicQuestion.fetch');
        Route::post('/basic-questions/answer', 'answer_basic_question')->name('basicQuestion.answer');
        Route::get('/interests', 'fetch_interests')->name('interest.fetch');
        Route::post('/interests', 'set_interests')->name('interest.set');
    });

    Route::controller(ControllerDassQuestionController::class)->group(function(){
        Route::get('/dass-questions', 'fetch_questions')->name('dassQuestion.fetch');
        Route::post('/dass-questions/answer', 'answer_dass_questions')->name('dassQuestion.answer');
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
});

Route::get('/test-token/{card}/{year}/{month}/{cvv}', [StripeController::class, 'create_token']);
Route::get('/test-customer/{name}/{email}', [StripeController::class, 'create_customer']);

Route::get('/test-gpt/{prompt}', [ChatGPTController::class, 'complete_chat']);