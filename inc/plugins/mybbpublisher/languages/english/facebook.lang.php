<?php

//required vars
$l['service_name'] = 'Facebook';

//custom vars
$l['step1'] = "Step 1: Verify App ID Setting";
$l['step2'] = "Step 2: Redirect to Facebook for authorization.";
$l['step3'] = "Step 3: Select where to post and specify author.";
$l['step4'] = "Step 4: Create/Delete a Test Post";

$l['step1_results'] = "Results of Step 1: Verify App ID Setting";
$l['step2_results'] = "Results of Step 2: Get Access Token for your Personal FB Account.";
$l['step3_results'] = "Results of Step 3: Select where to post and specify author.";
$l['step4_results'] = "Results of Step 4: Create/Delete a Test Post";

$l['appid_check'] = "The output should contain the name and description of your application as you entered it. If not, then the APP ID you provided is incorrect or the application is not correctly setup on Facebook";

$l['missing_appid'] = "You must provide the Application ID before proceeding.";
$l['missing_creds'] = "You must provide the Application ID and Application Secret before proceeding.";

$l['token_success'] = "Successfully obtained access token:";
$l['token_saved'] = "This token has been saved to your MyBBPublisher settings";

$l['manage_success'] = "Successfully updated where to post and whom to post as.";
$l['manage_error'] = "You do not appear to be the administrator of any apps, pages or groups. Please verify that you own the application you are trying to setup, it should have been listed here.";
$l['manage_own'] = "Always have the option to post to your own wall as ";

$l['test_message'] = "This is a test of the newly activated MyBB Publisher plugin";
$l['test_success'] = "Successfully deleted. Return value: ";
$l['test_failed'] = "Delete failed. Return value: ";
$l['test_return'] = "Return value of test post update:";
$l['test_made'] = "Verify test post has been made to your account/page then ";
$l['test_delete'] = "attempt to delete it";
$l['test_failed2'] = "Test post was not made. See output above.";

$l['posted_by_link'] = "{1} posted: {2}";
$l['posted_by_photo'] = "\"{2}\"\nposted by {1}\nat {3}";


$l['categorized'] = "Categorized as";
$l['post_to'] = "Post to ";
$l['as'] = " as ";
$l['name'] = "Name";
$l['id'] = "ID";
$l['token'] = "Access Token";
$l['expires'] = "Expires in (sec)";
$l['group'] = "Group";
$l['groups'] = "Groups";
$l['group'] = "Group";
$l['pages_apps'] = "Pages and Apps";
$l['wall'] = "Wall";

$l['error'] = "Something went wrong and no error information was provided.";

$l['setting_success'] = "Settings have been saved. If you have changed your App ID or App Secret, you must go through the setup again.";

$l['setting_enable'] = "Enable the Facebook module.";
$l['setting_enable_desc'] = "Check to start using the Facebook module.";

$l['setting_app_id'] = "Facebook Application ID";
$l['setting_app_id_desc'] = "This is the App ID for your application, available from the Facebook developer site.";

$l['setting_app_secret'] = "Facebook Application Secret";
$l['setting_app_secret_desc'] = "This is the App Secret for your application, available from the Facebook developer site.";

$l['setting_type'] = "Post Type when Images Attached";
$l['setting_type_desc'] = "This is the type of post to make to Facebook when there are images attached to the new thread. Link option is a basic link post with no images. Only the first attachment will be included if set to Photo.";
$l['setting_type_link'] = "Link";
$l['setting_type_photo'] = "Photo";
$l['setting_type_photo_no_albums'] = "There are no albums listed for the page and account you have configured.";
$l['setting_type_photo_no_list'] = "This setting is not available until you complete Step 3 of the setup and set Photo as the Type. Albums will be listed on the next page load when all details are present.";
$l['setting_type_photo_albums'] = "Albums";
$l['setting_type_photo_upload_to'] = "If there is an image attachment, upload to this Facebook album: (privacy setting of the album)";

$l['setting_icon'] = "Path to Facebook icon";
$l['setting_icon_desc'] = "Relative path to Facebook icon used to identify those forums to be published. Leave blank to disable.";

$l['setting_forums'] = "Forums to publish.";
$l['setting_forums_desc'] = "Select forums to publish. Selected categories are ignored.";

?>
