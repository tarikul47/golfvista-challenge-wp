<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://golfvista.com/
 * @since      1.0.0
 *
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/public/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="golfvista-challenge-wrapper">

    <h1>Brain & Beauty Challenge</h1>

    <div id="golfvista-challenge-content">
        <?php if ( 'not_logged_in' === $status ) : ?>
            <p>Please <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">log in</a> or <a href="<?php echo esc_url( wp_registration_url() ); ?>">register</a> to participate in the challenge.</p>
        <?php elseif ( 'not_started' === $status ) : ?>
            <p>Welcome to the challenge! Please upload exactly 5 of your best original photos or 30-second videos to begin.</p>
            <form id="media-submission-form" method="post">
                <div class="media-uploader-wrapper">
                    <button type="button" class="button" id="upload-media-button">Upload Media</button>
                    <div class="media-preview-wrapper"></div>
                    <input type="hidden" name="golfvista_media_ids" id="golfvista-media-ids" value="">
                </div>
                <?php wp_nonce_field( 'golfvista_media_submission_nonce' ); ?>
                <input type="submit" name="golfvista_media_submission" class="button button-primary" value="Submit Media" disabled>
            </form>
        <?php elseif ( 'media_uploaded' === $status ) : ?>
            <p>Thank you for your submission! Your media has been received and is pending verification. You will be notified shortly.</p>
        <?php elseif ( 'media_failed' === $status ) : ?>
            <p>Unfortunately, your submission did not pass our originality check. Please ensure your photos and videos are not AI-generated and <a href="<?php echo esc_url( add_query_arg( 'try_again', 'true' ) ); ?>">try again</a>.</p>
        <?php elseif ( 'media_approved' === $status ) :
            $main_options = get_option( 'golfvista_challenge_main' );
            $product_id = isset( $main_options['challenge_product_id'] ) ? $main_options['challenge_product_id'] : 0;
            if ( $product_id ) {
                $product_url = get_permalink( $product_id );
            } else {
                $product_url = '#'; // Fallback
            }
            ?>
            <p>Congratulations! Your submission has passed the first screening. Please <a href="<?php echo esc_url( $product_url ); ?>">proceed to payment</a> to enter the next stage of the challenge.</p>
        <?php elseif ( 'paid' === $status ) : 
            $quiz_options = get_option( 'golfvista_challenge_quiz' );
            $questions = isset( $quiz_options['questions'] ) ? $quiz_options['questions'] : array();
            ?>
            <p>Welcome to the quiz! Answer at least 4 out of 6 questions correctly to proceed.</p>
            <form id="quiz-submission-form" method="post">
                <?php foreach ( $questions as $i => $question ) : ?>
                    <div class="quiz-question">
                        <label for="question_<?php echo $i; ?>"><?php echo esc_html( $question['text'] ); ?></label>
                        <input type="text" id="question_<?php echo $i; ?>" name="answers[<?php echo $i; ?>]" />
                    </div>
                <?php endforeach; ?>
                <?php wp_nonce_field( 'golfvista_quiz_submission_nonce' ); ?>
                <input type="submit" name="golfvista_quiz_submission" class="button button-primary" value="Submit Answers">
            </form>
        <?php elseif ( 'quiz_failed' === $status ) : ?>
            <p>We're sorry, but you did not pass the quiz. Thank you for your participation.</p>
        <?php elseif ( 'quiz_passed' === $status ) : ?>
            <p>Congratulations! You have passed the quiz and made it to the final round. You are now invited to submit your business plan.</p>
            <form id="business-plan-submission-form" method="post">
                <div class="business-plan-wrapper">
                    <textarea name="business_plan_content" rows="10" cols="50" placeholder="Enter your business plan here..."></textarea>
                </div>
                <?php wp_nonce_field( 'golfvista_business_plan_submission_nonce' ); ?>
                <input type="submit" name="golfvista_business_plan_submission" class="button button-primary" value="Submit Business Plan">
            </form>
        <?php elseif ( 'plan_submitted' === $status ) : ?>
            <p>Thank you for submitting your business plan. We will review it and get back to you shortly.</p>
        <?php else : ?>
            <p>Challenge in progress. Your current status is: <?php echo esc_html( $status ); ?></p>
        <?php endif; ?>
    </div>

</div>

