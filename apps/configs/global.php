<?php
/**
 * PHPProject
 * global.php Created by usher.yue.
 * User: usher.yue
 * Date: 16/7/6
 * Time: 上午9:41
 * 心怀教育梦－烟台网格软件技术有限公司
 */
$GlobalConfig=[
    'UIA_API'=>[
        'GET_CLASS_STUDENT'=>'/api.php?s=/api/student/getClassstudentfields?bjid=%s&fields=%s'  //根据班级获取学生
    ]

];

/**获取uia配置
 * @param $key
 * @return mixed
 */
function UiaApiConfig($key){
    global $GlobalConfig;
    return $GlobalConfig['UIA_API'][$key];
}