<?php

//required vars
$l['service_name'] = 'Twitter';

//custom vars
$l['step1'] = "Step 1: Verify Credentials";
$l['step2'] = "Step 2: Check rate limit status.";
$l['step3'] = "Step 3: Show recent Tweets.";
$l['step4'] = "Step 4: Create/Delete a Test Post.";

$l['step1_results'] = "Results of Step 1: Verify Credentials";
$l['step2_results'] = "Results of Step 2: Check rate limit status.";
$l['step3_results'] = "Results of Step 3: Recent tweets.";
$l['step4_results'] = "Results of Step 4: Test post.";

$l['creds_check'] = "The output should contain the name and description of your application as you entered it. If not, then the settings you provided are incorrect or the application is not correctly setup on Twitter";
$l['name'] = "Name";
$l['url'] = "URL";
$l['description'] = "Description";
$l['id'] = "ID";

$l['rate_limit'] = "Returns the remaining number of API requests available to the requesting user before the API limit is reached for the current hour/day.";
$l['remaining_hits'] = "Remaining hits";
$l['hourly_limit'] = "Hourly Limit";
$l['reset_time'] = "Resets at";
$l['daily_limit'] = "Daily Limit";
$l['photo_limit'] = "Photo Upload Limits";
$l['text_limit'] = "Status Update Limits";

$l['recent'] = "The 5 most recent Tweets.";
$l['empty'] = "There are no recent Tweets for the specified account.";
$l['created'] = "Posted at";
$l['text'] = "Status message";

$l['at'] = " at ";

//these two not used due to short limit on tweets
$l['posted_by_link'] = "{1} posted {2}";
$l['posted_by_photo'] = "\"{2}\" was posted by {1}";

$l['test_message'] = "This is a test of the newly activated MyBB Publisher plugin";
$l['test_success'] = "Successfully deleted. ";
$l['test_failed'] = "Delete failed. Return value: ";
$l['test_return'] = "Return value of test post update:";
$l['test_made'] = "Verify test post has been made to your account then ";
$l['test_delete'] = "attempt to delete it";
$l['test_failed2'] = "Test post was not made. See output above.";


$l['error'] = "Error";
$l['error_info'] = "Something went wrong. The following was provided.";
$l['error_noinfo'] = "Something went wrong and no error information was provided.";

$l['setting_success'] = "Settings have been saved. If you have changed your App ID or App Secret, you must go through the setup again.";

$l['setting_enable'] = "Enable the Twitter module.";
$l['setting_enable_desc'] = "Check to start using the Twitter module.";

$l['setting_ckey'] = "OAuth Consumer Key";
$l['setting_ckey_desc'] = "This is the Consumer Key for your application, available from the Twitter developer site.";

$l['setting_csecret'] = "OAuth Consumer Secret";
$l['setting_csecret_desc'] = "This is the Consumer Secret for your application, available from the Twitter developer site.";

$l['setting_token'] = "Access Token";
$l['setting_token_desc'] = "This is the Access Token for your Twitter account, available from the Twitter developer site.";

$l['setting_tsecret'] = "Access Token Secret";
$l['setting_tsecret_desc'] = "This is the Access Token Secret for your Twitter account, available from the Twitter developer site.";

$l['setting_icon'] = "Path to Twitter icon";
$l['setting_icon_desc'] = "Relative path to Twitter icon used to identify those forums to be published. Leave blank to disable.";

$l['setting_type'] = "Post Type when Images Attached";
$l['setting_type_desc'] = "This is the type of post to make to Twitter when there are images attached to the new thread. Link option is a basic link post with no images. Only the first attachment will be included if set to Photo.";
$l['setting_type_link'] = "Link";
$l['setting_type_photo'] = "Photo";

$l['setting_tags'] = "Hash tags to add to each post.";
$l['setting_tags_desc'] = "A CSV list of tags (no hash) to include with all posts.";

?>
