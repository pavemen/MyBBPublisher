<?php

//required vars
$l['service_name'] = 'Tumblr';

//custom vars
$l['step1'] = "Step 1: Verify Credentials";
$l['step2'] = "Step 2: Authorize App in Tumblr.";
$l['step3'] = "Step 3: Show recent posts.";
$l['step4'] = "Step 4: Create/Delete a Test Post.";

$l['step1_results'] = "Results of Step 1: Verify Credentials";
$l['step2_results'] = "Results of Step 2: Request Authorization";
$l['step3_results'] = "Results of Step 3: Recent posts.";
$l['step4_results'] = "Results of Step 4: Test post.";

$l['creds_check'] = "The output should contain the name and description of your application as you entered it. If not, then the settings you provided are incorrect or the application is not correctly setup on Tumblr";
$l['name'] = "Name";
$l['url'] = "URL";
$l['description'] = "Description";

$l['posted_by_link'] = "{1} posted {2}";
$l['posted_by_link'] = "<a href=\"{3}\" target=\"_blank\">{2}</a> posted &quot;{1}&quot;";
$l['posted_by_photo'] = "<a href=\"{4}\" target=\"_blank\">{3}</a> was posted by <a href=\"{2}\" target=\"_blank\">{1}</a>";


$l['token'] = "Token";
$l['token_secret'] = "Token Secret";
$l['token_success'] = "Successfully obtained access token:";
$l['token_saved'] = "This token has been saved to your MyBBPublisher settings";
$l['failed_auth'] = "Authorization failed or was denied.";

$l['recent'] = "The 5 most recent posts.";
$l['empty'] = "There are no recent posts for the specified account.";
$l['created'] = "Posted at";
$l['title'] = "Title";
$l['body'] = "Message body";
$l['type'] = "Type";
$l['caption'] = "Caption";
$l['photos'] = "Photos";
$l['nophotos'] = "No photos found";
$l['text'] = "Text";
$l['source'] = "Source";

$l['test_title'] = "This is a just a test.";
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
$l['error_code'] = "Error code";
$l['error_msg'] = "Error message";

$l['setting_success'] = "Settings have been saved. If you have changed your App ID or App Secret from a previous value, you must go through Step 2 to renew your credentials.";

$l['setting_enable'] = "Enable the Tumblr module.";
$l['setting_enable_desc'] = "Check to start using the Tumblr module.";

$l['setting_ckey'] = "OAuth Consumer Key";
$l['setting_ckey_desc'] = "This is the Consumer Key for your application, available from the Tumblr developer site.";

$l['setting_csecret'] = "OAuth Consumer Secret";
$l['setting_csecret_desc'] = "This is the Consumer Secret for your application, available from the Tumblr developer site.";

$l['setting_hostname'] = "Hostname";
$l['setting_hostname_desc'] = "This is the hostname (standard or custom) for your Tumblr account, essentially Tumblr URL without the http://.";

$l['setting_type'] = "Post Type when Images Attached";
$l['setting_type_desc'] = "This is the type of post to make to Tumblr when there are images attached to the new thread. Link option is a basic link post with no images. Only the first attachment will be included if set to Photo.";
$l['setting_type_link'] = "Link";
$l['setting_type_photo'] = "Photo";

$l['setting_icon'] = "Path to Tumblr icon";
$l['setting_icon_desc'] = "Relative path to Tumblr icon used to identify those forums to be published. Leave blank to disable.";

$l['setting_tags'] = "Tags to add to each post.";
$l['setting_tags_desc'] = "A CSV list of tags (no hash) to include with all posts.";

$l['setting_state'] = "Default state of the post";
$l['setting_state_desc'] = "This is the default state of the post. 'Published' is recommended. <strong>Note</strong>: that setting this to Queue or Draft will make it so that the update can not be deleted automatically by MyBB Publisher as manually publishing from Queue or Draft via the Tumblr interface changes the ID of the item.";

$l['setting_state_published'] = "Published";
$l['setting_state_draft'] = "Draft";
$l['setting_state_queue'] = "Queue";
$l['setting_state_private'] = "Private";

$l['setting_forums'] = "Forums to publish.";
$l['setting_forums_desc'] = "Select forums to publish. Selected categories are ignored.";

?>
