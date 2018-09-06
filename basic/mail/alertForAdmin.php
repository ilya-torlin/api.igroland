<head>
    <meta charset="utf-8"/>
</head>
<body>
<link href='https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300,700&subset=latin,cyrillic' rel='stylesheet' type='text/css'>
<table width="100%" height="100%" style="background-image: url('http://praweb.ru/email-image/pattern.png'); color: #2f3850; font-family: 'Open Sans Condensed', sans-serif;">
    <tr>
        <td valign="middle" align="center">
            <table width="450" style=" padding-top: 10px">
                <tr>
                    <td>
                        <table width="430" valign="top" align="center">
                            <tr>
                                <td>
                                    <table valign="top" align="left" style="background-color: #fcfcfe; padding: 8px; padding-top: 14px ; border-width: 1px; border-style:solid; border-color: #eef0f7">
                                        <tr>
                                            <th style="height: 200px; width:200px; border-width: 1px; border-style:solid; border-color: #eef0f7">
                                                 <img src="https://praweb.ru/email-image/logo.png" alt="Theme"/>
                                            </th>
                                            <th align="left" style="width: 207px; height: 200px; padding-left: 10px">
                                                <table style="width: 100%; height: 100%; padding-top: 15px"
                                                       valign="top">
                                                    <tr style="height: 22px">
                                                        <th style="width: 20px">
                                                            <img src="https://praweb.ru/email-image/star.png" alt="Theme"/>
                                                        </th>
                                                        <th align="left" style="color: #2f3850; font-weight: bold; ">
                                                            ТЕМА ПИСЬМА
                                                        </th>
                                                    </tr>

                                                    <tr>
                                                        <td colspan="2" style="color: #2f3850; text-decoration: underline;" valign="top">
                                                            <?php if ($level == 1){ ?>
                                                            Превышение максимального времени по проекту <?php echo $alert->project->name;?>
                                                            <?php } else { ?>
                                                             Предупреждение о приближении максимального времени по проекту <?php echo $alert->project->name;?>
                                                            
                                                            <?php }?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding: 0;">
                                                <table style="width: 100%; padding: 8px 0" valign="middle">
                                                    <tr style="padding: 0;">
                                                        <td style="padding: 4px 0">
                                                            <table cellspacing="0"
                                                                   style="width: 100%; background-color: #eef0f7; border-color: #eef0f7; border-width: 2px; border-style: solid">
                                                                <tr style="height: 25px">
                                                                    <td style="width: 25px; background-color: #fff"
                                                                        valign="middle" align="center">
                                                                        <img src="http://praweb.ru/email-image/star.png" alt="имя"/>
                                                                    </td>
                                                                    <td style="font-weight: bold; text-transform: uppercase; color: #2f3850; width:130px; background-color: #fff">
                                                                        Проект:
                                                                    </td>
                                                                    <td style="font-weight: bold; text-transform: uppercase; color: #2f3850; padding-left: 10px"><?php echo $alert->project->name;?></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr style="padding: 0;">
                                                        <td style="padding: 4px 0">
                                                            <table cellspacing="0"
                                                                   style="width: 100%; background-color: #eef0f7; border-color: #eef0f7; border-width: 2px; border-style: solid">
                                                                <tr style="height: 25px">
                                                                    <td style="width: 25px; background-color: #fff"
                                                                        valign="middle" align="center">
                                                                        <img src="http://praweb.ru/email-image/ipTarget.png" alt="имя"/>
                                                                    </td>
                                                                    <td style="font-weight: bold; text-transform: uppercase; color: #2f3850; width:130px; background-color: #fff">
                                                                        Отдел:
                                                                    </td>
                                                                    <td style="font-weight: bold; text-transform: uppercase; color: #2f3850; padding-left: 10px"> <?php echo $alert->department->name;?></td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr style="padding: 0;">
                                                        <td style="padding: 4px 0">
                                                            <table cellspacing="0"
                                                                   style="width: 100%; background-color: #eef0f7; border-color: #eef0f7; border-width: 2px; border-style: solid">
                                                                <tr style="height: 25px">
                                                                    <td style="width: 25px; background-color: #fff"
                                                                        valign="middle" align="center">
                                                                        <img src="http://praweb.ru/email-image/ipTarget.png" alt="имя"/>
                                                                    </td>
                                                                    <td style="font-weight: bold; text-transform: uppercase; color: #2f3850; width:130px; background-color: #fff">
                                                                        Лимит:
                                                                    </td>
                                                                    <td style="font-weight: bold; text-transform: uppercase; color: #2f3850; padding-left: 10px"> <?php echo $alert->value;?> ч.</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr style="padding: 0;">
                                                        <td style="padding: 4px 0">
                                                            <table cellspacing="0"
                                                                   style="width: 100%; background-color: #eef0f7; border-color: #eef0f7; border-width: 2px; border-style: solid">
                                                                <tr style="height: 25px">
                                                                    <td style="width: 25px; background-color: #fff"
                                                                        valign="middle" align="center">
                                                                        <img src="http://praweb.ru/email-image/ipTarget.png" alt="имя"/>
                                                                    </td>
                                                                    <td style="font-weight: bold; text-transform: uppercase; color: #2f3850; width:130px; background-color: #fff">
                                                                        Потрачено:
                                                                    </td>
                                                                    <td style="font-weight: bold; text-transform: uppercase; color: #2f3850; padding-left: 10px"> <?php echo (int)($sum / 60);?> ч.</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    
                                                    
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
