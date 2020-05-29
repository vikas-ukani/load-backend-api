<?php

/** Status responses Codes */
define('RESPONSE_CODE_SUCCESS', 200); // OK
define('RESPONSE_BAD_REQUEST', 400); //  Bad Request
define('RESPONSE_UNAUTHORIZED_REQUEST', 401); //  Unauthorized
define('RESPONSE_FORBIDDEN_REQUEST', 403); //    Forbidden
define('RESPONSE_INTERNAL_SERVER_ERROR', 500); //    Internal Server Error
define('RESPONSE_NOT_LOGIN_USER', 1000); //    Internal Server Error

/** uploaded main folder name */
define('UPLOADED_FOLDER_NAME', '/uploaded/');

/** Image File Extension */
define('PNG_EXTENSION', '.png'); //

const LIBRARY_BODY_PART_IMAGE_BACK_URL = UPLOADED_FOLDER_NAME . "images/body_parts/Anatomy_Back";
const LIBRARY_BODY_PART_IMAGE_FRONT_URL = UPLOADED_FOLDER_NAME . "images/body_parts/Anatomy_Front";

/**
 * Registration Steps
 */
define('REGISTRATION_STEP_SIGNUP', 1);
define('REGISTRATION_STEP_COMPLETE_PROFILE', 2);

/** gender constants */
define('GENDER_MALE', "MALE");
define('GENDER_FEMALE', "FEMALE");
define('GENDER_OTHER', "OTHER");

/**
 * Define User Types
 */
define('USER_TYPE_SUPERADMIN', 'SUPERADMIN');
define('USER_TYPE_ADMIN', 'ADMIN');
define('USER_TYPE_USER', 'USER');

/**
 * Define User Account Types
 */
define('ACCOUNT_TYPE_FREE', 'FREE');
define('ACCOUNT_TYPE_PREMIUM', 'PREMIUM');
define('ACCOUNT_TYPE_PROFESSIONAL', 'PROFESSIONAL');

/** Account Free Trials Days */
define('ACCOUNT_FREE_TRIAL_DAYS', 30);

define("MODULE_NAME_CALENDER", "CALENDER");
define("MODULE_NAME_LIBRARY", "LIBRARY");
define("MODULE_NAME_MESSAGE", "MESSAGE");
define("MODULE_NAME_LOAD_CENTER", "LOAD_CENTER");
define("MODULE_NAME_SETTING", "SETTING");

define("CALENDER_TRAINING_PROGRAM", "TRAINING_PROGRAM");
define("CALENDER_TRAINING_LOG", "TRAINING_LOG");

/** training log status */
define("TRAINING_LOG_STATUS_CARDIO", "CARDIO");
define("TRAINING_LOG_STATUS_RESISTANCE", "RESISTANCE");

/** training program status */
define("TRAINING_PROGRAM_STATUS_CARDIO", "CARDIO");
define("TRAINING_PROGRAM_STATUS_RESISTANCE", "RESISTANCE");

/** training program type */
define("TRAINING_PROGRAM_TYPE_PRESET", "PRESET");
define("TRAINING_PROGRAM_TYPE_CUSTOM", "CUSTOM");

const TRAINING_ACTIVITY_CODE_RUN_OUTDOOR = "RUN_OUTDOOR";
const TRAINING_ACTIVITY_CODE_RUN_INDOOR = "RUN_INDOOR";
const TRAINING_ACTIVITY_CODE_CYCLE_OUTDOOR = "CYCLE_OUTDOOR";
const TRAINING_ACTIVITY_CODE_CYCLE_INDOOR = "CYCLE_INDOOR";
const TRAINING_ACTIVITY_CODE_SWIMMING = "SWIMMING";
const TRAINING_ACTIVITY_CODE_OTHERS = "OTHERS";

/** Training Session type */
define("TRAINING_SESSION_COMPLETED", 'COMPLETED');
define("TRAINING_SESSION_UPCOMING", 'UPCOMING');

/** By start date or end date => use in preset -> training program*/
const PRESET_TRAINING_PROGRAM_START = "START";
const PRESET_TRAINING_PROGRAM_END = "END";

/** library list constant */
const LIBRARY_LIST_UPPER = "UPPER";
const LIBRARY_LIST_LOWER = "LOWER";
const LIBRARY_LIST_TRUNK = "TRUNK";
const LIBRARY_LIST_FAVORITE = "FAVORITE";

/**
 * Load Center Status
 */
const LOAD_CENTER_STATUS_FEED = "FEED";
const LOAD_CENTER_STATUS_REQUEST = "REQUEST";
const LOAD_CENTER_STATUS_EVENT = "EVENT";
const LOAD_CENTER_STATUS_LISTING = "LISTING";

/**
 * Load Center Event Visibility
 */
const LOAD_CENTER_EVENT_VISIBILITY_INVITATION_ONLY = "INVITATION_ONLY";
const LOAD_CENTER_EVENT_VISIBILITY_PUBLIC = "PUBLIC";

/**
 * User Relation Status Constants
 */
const USER_RELATION_STATUS_PROFESSIONAL_SPECIALIZATION  = "PROFESSIONAL_SPECIALIZATION";

/**
 * Session Types
 */
const PROFESSIONAL_PROFILE_SESSION_TYPE_SINGLE = 'SINGLE';
const PROFESSIONAL_PROFILE_SESSION_TYPE_MULTIPLE = 'MULTIPLE';

/**
 * Constants for Professional user availability
 */
const PROFESSIONAL_AVAILABILITY_ANY_DAY = "ANY_DAY";
const PROFESSIONAL_AVAILABILITY_WEEKDAYS_ONLY = "WEEKDAYS_ONLY";
const PROFESSIONAL_AVAILABILITY_WEEKENDS_ONLY = "WEEKENDS_ONLY";
const PROFESSIONAL_AVAILABILITY_MESSAGE_TO_DISCUSS = "MESSAGE_TO_DISCUSS";

const PROFESSIONAL_AVAILABILITY_DAYS_MONDAY = "MONDAY";
const PROFESSIONAL_AVAILABILITY_DAYS_TUESDAY = "TUESDAY";
const PROFESSIONAL_AVAILABILITY_DAYS_WEDNESDAY = "WEDNESDAY";
const PROFESSIONAL_AVAILABILITY_DAYS_THURSDAY = "THURSDAY";
const PROFESSIONAL_AVAILABILITY_DAYS_FRIDAY = "FRIDAY";
const PROFESSIONAL_AVAILABILITY_DAYS_SUNDAY = "SUNDAY";

/** Training Plans Types */
const COMMON_PROGRAMS_PLAN_TYPE_5K = "5K";
const COMMON_PROGRAMS_PLAN_TYPE_10K = "10K";
const COMMON_PROGRAMS_PLAN_TYPE_21K = "21K";
const COMMON_PROGRAMS_PLAN_TYPE_42K = "42K";

/** Frequency type */
const COMMON_PROGRAMS_FREQUENCY_4 = "4";
const COMMON_PROGRAMS_FREQUENCY_5 = "5";
const COMMON_PROGRAMS_FREQUENCY_6 = "6";

/** Common weeks list */
const COMMON_PROGRAMS_WEEKS_WEEK_1 = "WEEK_1";
const COMMON_PROGRAMS_WEEKS_WEEK_2 = "WEEK_2";
const COMMON_PROGRAMS_WEEKS_WEEK_3 = "WEEK_3";
const COMMON_PROGRAMS_WEEKS_WEEK_4 = "WEEK_4";
const COMMON_PROGRAMS_WEEKS_WEEK_5 = "WEEK_5";
const COMMON_PROGRAMS_WEEKS_WEEK_6 = "WEEK_6";
const COMMON_PROGRAMS_WEEKS_WEEK_7 = "WEEK_7";
const COMMON_PROGRAMS_WEEKS_WEEK_8 = "WEEK_8";
const COMMON_PROGRAMS_WEEKS_WEEK_9 = "WEEK_9";
const COMMON_PROGRAMS_WEEKS_WEEK_10 = "WEEK_10";

const COMMON_PROGRAMS_WEEKS_WEEK_11 = "WEEK_11";
const COMMON_PROGRAMS_WEEKS_WEEK_12 = "WEEK_12";
const COMMON_PROGRAMS_WEEKS_WEEK_13 = "WEEK_13";
const COMMON_PROGRAMS_WEEKS_WEEK_14 = "WEEK_14";
const COMMON_PROGRAMS_WEEKS_WEEK_15 = "WEEK_15";
const COMMON_PROGRAMS_WEEKS_WEEK_16 = "WEEK_16";
const COMMON_PROGRAMS_WEEKS_WEEK_17 = "WEEK_17";
const COMMON_PROGRAMS_WEEKS_WEEK_18 = "WEEK_18";
const COMMON_PROGRAMS_WEEKS_WEEK_19 = "WEEK_19";
const COMMON_PROGRAMS_WEEKS_WEEK_20 = "WEEK_20";

/** Training Program Workout  */
const COMMON_PROGRAMS_WORKOUT_1 = "1";
const COMMON_PROGRAMS_WORKOUT_2 = "2";
const COMMON_PROGRAMS_WORKOUT_3 = "3";
const COMMON_PROGRAMS_WORKOUT_4 = "4";
const COMMON_PROGRAMS_WORKOUT_5 = "5";

/** Training Program Base */
const COMMON_PROGRAMS_BASE_1 = "1";
const COMMON_PROGRAMS_BASE_2 = "2";

/**
 * training goal display status
 */
const TRAINING_GOAL_LOG_CARDIO = "LOG_CARDIO";
const TRAINING_GOAL_LOG_RESISTANCE = "LOG_RESISTANCE";
const TRAINING_GOAL_PROGRAM_CARDIO = "PROGRAM_CARDIO";
const TRAINING_GOAL_PROGRAM_RESISTANCE = "PROGRAM_RESISTANCE";

/**
 * Training Log  Workout Stop Immediately
 */
const TRAINING_WORKOUT_STOP_IMMEDIATELY_MIN_SECOND = 0;
const TRAINING_WORKOUT_STOP_IMMEDIATELY_MAX_SECOND  = 30;

/** setting contacts */
const SETTING_EMERGENCY_CONTACT_1 = "+65 1234 5678";
const SETTING_EMERGENCY_CONTACT_2 = "+65 1234 5679";
