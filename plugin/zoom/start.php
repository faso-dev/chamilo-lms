<?php

/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

api_protect_course_script(true);

// the section (for the tabs)
$this_section = SECTION_COURSES;
$logInfo = [
    'tool' => 'Videoconference Zoom',
];
Event::registerLog($logInfo);

$course = api_get_course_entity();
if (null === $course) {
    api_not_allowed(true);
}

$group = api_get_group_entity();
$session = api_get_session_entity();
$plugin = ZoomPlugin::create();

if (api_is_in_group()) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.$group->getName(),
    ];
}

$tool_name = $plugin->get_lang('ZoomVideoConferences');
$tpl = new Template($tool_name);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

$isManager = $plugin->userIsCourseConferenceManager();
if ($isManager) {
    switch ($action) {
        case 'delete':
            $meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $_REQUEST['meetingId']]);
            if ($meeting && $meeting->isCourseMeeting()) {
                $plugin->deleteMeeting($meeting, api_get_self().'?'.api_get_cidreq());
            }
            break;
    }

    $user = api_get_user_entity(api_get_user_id());
    // user can create a new meeting
    $tpl->assign(
        'createInstantMeetingForm',
        $plugin->getCreateInstantMeetingForm(
            $user,
            $course,
            $group,
            $session
        )->returnForm()
    );
    $tpl->assign(
        'scheduleMeetingForm',
        $plugin->getScheduleMeetingForm(
            $user,
            $course,
            $group,
            $session
        )->returnForm()
    );
}

try {
    $tpl->assign(
        'scheduledMeetings',
        $plugin->getMeetingRepository()->courseMeetings($course, $group, $session)
    );
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message('Could not retrieve scheduled meeting list: '.$exception->getMessage(), 'error')
    );
}

$tpl->assign('is_manager', $isManager);
$tpl->assign('content', $tpl->fetch('zoom/view/start.tpl'));
$tpl->display_one_col_template();