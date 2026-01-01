<?php

/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://golfvista.com/
 * @since      1.0.0
 *
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/admin/partials
 */
?>

<div class="wrap">
    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <h2 class="nav-tab-wrapper">
        <a href="?page=golfvista-challenge&tab=main" class="nav-tab <?php echo $active_tab == 'main' ? 'nav-tab-active' : ''; ?>">Main Settings</a>
        <a href="?page=golfvista-challenge&tab=quiz" class="nav-tab <?php echo $active_tab == 'quiz' ? 'nav-tab-active' : ''; ?>">Quiz Settings</a>
    </h2>

    <form action="options.php" method="post">
        <?php
        if ( $active_tab == 'main' ) {
            settings_fields( 'golfvista_challenge_main' );
            $options = get_option( 'golfvista_challenge_main' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Sightengine API User</th>
                    <td><input type="text" name="golfvista_challenge_main[sightengine_api_user]" value="<?php echo esc_attr( $options['sightengine_api_user'] ); ?>" size="40" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Sightengine API Secret</th>
                    <td><input type="text" name="golfvista_challenge_main[sightengine_api_secret]" value="<?php echo esc_attr( $options['sightengine_api_secret'] ); ?>" size="40" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Challenge Product ID</th>
                    <td><input type="text" name="golfvista_challenge_main[challenge_product_id]" value="<?php echo esc_attr( $options['challenge_product_id'] ); ?>" size="10" /></td>
                </tr>
            </table>
            <?php
        } else {
            settings_fields( 'golfvista_challenge_quiz' );
            $options = get_option( 'golfvista_challenge_quiz' );
            ?>
            <table class="form-table" id="quiz-questions-table">
                <?php
                $questions = isset( $options['questions'] ) ? $options['questions'] : array();
                $question_count = count( $questions );
                for ( $i = 0; $i < 6; $i++ ) {
                    $style = ( $i >= $question_count && $i > 0 ) ? 'style="display:none;"' : '';
                    ?>
                    <tr valign="top" class="quiz-question-row" <?php echo $style; ?>>
                        <th scope="row">Question <?php echo $i + 1; ?></th>
                        <td>
                            <input type="text" name="golfvista_challenge_quiz[questions][<?php echo $i; ?>][text]" value="<?php echo esc_attr( $questions[$i]['text'] ); ?>" placeholder="Question" size="50" /><br>
                            <input type="text" name="golfvista_challenge_quiz[questions][<?php echo $i; ?>][keyword]" value="<?php echo esc_attr( $questions[$i]['keyword'] ); ?>" placeholder="Keyword" size="30" />
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <button type="button" class="button" id="add-quiz-question">Add Question</button>
            <?php
        }
        submit_button();
        ?>
    </form>
</div>
<script>
jQuery(document).ready(function($) {
    var questionRowCount = $('.quiz-question-row').length;
    $('#add-quiz-question').on('click', function() {
        var hiddenRows = $('.quiz-question-row:hidden');
        if (hiddenRows.length > 0) {
            $(hiddenRows[0]).show();
            if(hiddenRows.length === 1) {
                $(this).hide();
            }
        }
    });
});
</script>
