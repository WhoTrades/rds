<?php

use app\components\Status;
use app\models\ReleaseRequest;
use yii\db\Migration;

class m171003_124859_fix_destroyed_status extends Migration
{
    public function up()
    {
        $allowedList = ['76.00.1486.2767', '76.00.1487.2768', '76.00.1488.2769', '76.00.1489.2770', '76.00.1490.2771', '76.00.1491.2772', '76.00.1492.2773',
            '76.00.1493.2774', '76.00.1494.2775', '76.00.1495.2776', '76.00.1496.2777', '76.00.1497.2778', '76.00.1498.2779', '76.00.1499.2780', '76.00.1500.2781',
            '76.00.1501.2782', '76.00.1502.2783', '76.00.1503.2784', '76.00.1504.2785', '76.00.1505.2786', '76.00.1506.2787', '76.00.1507.2788', '76.00.1508.2789',
            '76.00.1509.2790', '76.00.1510.2791', '76.00.1511.2792', '76.00.1512.2793', '76.00.1513.2794', '76.00.1514.2795', '76.00.1515.2796', '76.00.1579.2412',
            '76.00.1580.2413', '76.00.1616.2449', '76.00.1617.2450', '76.00.1641.2474', '76.00.1699.2532', '76.00.1703.2536', '76.00.1704.2537', '76.00.1705.2538',
            '76.00.1706.2539', '76.00.1707.2540', '76.00.1708.2541', '76.00.1709.2542', '76.00.1710.2543', '76.00.1711.2544', '76.00.1712.2545', '76.00.1713.2546',
            '76.00.1714.2547', '76.00.1715.2548', '76.00.1716.2549', '76.00.1717.2550', '76.00.1718.2551', '76.00.1719.2552', '76.00.1720.2553', '76.00.1721.2554',
            '76.00.1722.2555', '76.00.1723.2556', '76.00.1724.2557', '76.00.1725.2558', '76.00.1726.2559', '76.00.1727.2560', '76.00.1728.2561', '76.00.1729.2562',
            '76.00.1730.2563', '76.00.1731.2564', '76.00.1732.2565', '76.00.1733.2566', '76.00.1734.2567', '76.00.1735.2568', '76.00.1736.2569', '76.00.1737.2570',
            '76.00.1738.2571', '76.00.1739.2572', '76.00.1740.2573', '76.00.1741.2574', '76.00.001.1', '76.00.012.12', '76.00.013.13', '76.00.014.14',
            '76.00.015.15', '76.00.016.16', '76.00.017.17', '76.00.018.18', '76.00.019.19', '76.00.020.20', '76.00.021.21', '76.00.022.22',
            '76.00.023.23', '76.00.024.24', '76.00.025.25', '76.00.026.26', '76.00.027.27', '76.00.028.28', '76.00.029.29', '76.00.030.30',
            '76.00.031.31', '76.00.032.32', '76.00.033.33', '76.00.034.34', '76.00.035.35', '76.00.036.36', '76.00.037.37', '76.00.005.8',
            '76.00.006.9', '76.00.007.10', '76.00.008.11', '76.00.001.1', '76.00.002.2', '76.00.003.3', '76.00.004.4', '76.00.005.5',
            '76.00.006.6', '76.00.007.7', '76.00.008.8', '76.00.009.9', '76.00.010.10', '76.00.011.11', '76.00.012.12', '76.00.013.13',
            '76.00.014.14', '76.00.015.15', '76.00.016.16', '76.00.017.17', '76.00.018.18', '76.00.019.19', '76.00.020.20', '76.00.021.21',
            '76.00.022.22', '76.00.132.132', '76.00.133.133', '76.00.134.134', '76.00.135.135', '76.00.136.136', '76.00.054.54', '76.00.055.55',
            '76.00.056.56', '76.00.057.57', '76.00.058.58', '76.00.011.11', '76.00.012.12', '76.00.013.13', '76.00.014.14', '76.00.129.129',
            '76.00.130.130', '76.00.131.131', '76.00.132.132', '76.00.133.133', '76.00.134.134', '76.00.135.135', '76.00.136.136', '76.00.137.137',
            '76.00.138.138', '76.00.139.139', '76.00.140.140', '76.00.077.77', '76.00.079.79', '76.00.093.93', '76.00.094.94', '76.00.095.95',
            '76.00.096.96', '76.00.097.97', '76.00.098.98', '76.00.099.99', '76.00.100.100', '76.00.101.101', '76.00.102.102', '76.00.103.103',
            '76.00.104.104', '76.00.001.1', '76.00.002.2', '76.00.003.3', '76.00.004.4', '76.00.005.5', '76.00.006.6', '76.00.007.7',
            '76.00.008.8', '76.00.009.9', '76.00.010.10', '76.00.011.11', '76.00.012.12', '76.00.013.13', '76.00.014.14', '76.00.015.15',
            '76.00.016.16', '76.00.017.17', '76.00.029.81', '76.00.621.1099', '76.00.646.1124', '76.00.647.1125', '76.00.648.1126', '76.00.649.1127',
            '76.00.650.1128', '76.00.651.1129', '76.00.652.1130', '76.00.653.1131', '76.00.654.1132', '76.00.655.1133', '76.00.656.1134', '76.00.657.1135',
            '76.00.658.1136', '76.00.014.14', '76.00.015.15', '76.00.016.16', '76.00.037.128', '76.00.043.134', '76.00.044.135', '76.00.045.136',
            '76.00.046.137', '76.00.047.138', '76.00.048.139', '76.00.049.140', '76.00.050.141', '76.00.051.142', '76.00.052.143', '76.00.053.144',
            '76.00.054.145', '76.00.055.146', '76.00.056.147', '76.00.057.148', '76.00.058.149', '2014.55.00.1.1', '63.00.001.2', '68.00.001.3',
            '71.00.001.4', '76.00.006.10', '76.00.007.11', '76.00.008.12', '76.00.009.13', '76.00.010.14', '76.00.011.15', '76.00.003.11',
            '76.00.076.76', '76.00.079.79', '76.00.080.80', '76.00.081.81', '76.00.082.82', '76.00.083.83', '76.00.084.84', '76.00.085.85',
            '76.00.086.86', '76.00.087.87', '76.00.088.88', '76.00.089.89', '76.00.090.90', '76.00.091.91', '76.00.092.92', '76.00.093.93',
            '76.00.027.77', '76.00.028.78', '76.00.029.79', '76.00.234.429', '76.00.235.430', '76.00.236.431', '76.00.237.432', '76.00.238.433',
            '76.00.239.434', '76.00.240.435', '76.00.241.436', '76.00.242.437', '76.00.243.438', '76.00.244.439', '76.00.245.440', '76.00.246.441',
            '76.00.247.442', '76.00.248.443', '76.00.249.444', '76.00.250.445', '76.00.251.446', '76.00.252.447', '76.00.253.448', '76.00.254.449',
            '76.00.255.450', '76.00.256.451', '76.00.257.452', '76.00.258.453', '76.00.259.454', '76.00.001.1', '76.00.002.2', '76.00.003.3',
            '76.00.004.4', '76.00.005.5', '76.00.006.6', '76.00.007.7', '76.00.008.8', '76.00.009.9', '76.00.010.10', '76.00.011.11',
            '76.00.012.12', '76.00.017.17', '76.00.018.18', '76.00.019.19', '76.00.020.20', '76.00.021.21', '76.00.022.22', '76.00.041.41',
            '76.00.042.42', '76.00.043.43', '76.00.044.44', '76.00.045.45', '76.00.046.46', '76.00.047.47', '76.00.048.48', '76.00.049.49',
            '76.00.050.50', '76.00.051.51', '76.00.052.52', '76.00.053.53', '76.00.054.54', '76.00.055.55', '76.00.056.56', '76.00.057.57',
            '76.00.058.58', '76.00.059.59', '76.00.060.60', '76.00.061.61', '76.00.062.62', '76.00.063.63', '76.00.064.64', '76.00.065.65',
            '76.00.066.66', '76.00.145.145', '76.00.146.146', '76.00.147.147', '76.00.148.148', '76.00.149.149', '76.00.150.150', '76.00.151.151',
            '76.00.152.152', '76.00.153.153', '76.00.154.154', '76.00.155.155', '76.00.156.156', '76.00.157.157', '76.00.158.158', '76.00.159.159',
            '76.00.160.160', '76.00.161.161', '76.00.162.162', '76.00.163.163', '76.00.164.164', '76.00.165.165', '76.00.166.166', '76.00.167.167',
            '76.00.168.168', '76.00.169.169', '76.00.170.170', '76.00.308.613', '76.00.320.625', '76.00.326.631', '76.00.328.633', '76.00.331.636',
            '76.00.333.638', '76.00.334.639', '76.00.336.641', '76.00.337.642', '76.00.338.643', '76.00.339.644', '76.00.340.645', '76.00.341.646',
            '76.00.342.647', '76.00.343.648', '76.00.344.649', '76.00.345.650', '76.00.346.651', '76.00.347.652', '76.00.348.653', '76.00.349.654',
            '76.00.350.655', '76.00.351.656', '76.00.352.657', '76.00.353.658', '76.00.354.659', '76.00.355.660', '76.00.356.661', '76.00.360.665',
            '76.00.361.666', '76.00.362.667', '76.00.363.668', '76.00.364.669', '76.00.365.670', '76.00.366.671', '76.00.367.672', '76.00.368.673',
            '76.00.369.674', '76.00.370.675', '76.00.371.676', '76.00.372.677', '76.00.373.678', '76.00.374.679', '76.00.037.121', '76.00.038.122',
            '76.00.039.123', '76.00.040.124', '76.00.041.125', '76.00.042.126', '76.00.043.127', '76.00.044.128', '76.00.045.129', '76.00.046.130',
            '76.00.047.131', '76.00.048.132', '76.00.049.133', '76.00.050.134', '76.00.051.135', '76.00.012.19', '76.00.013.20', '76.00.014.21',
            '76.00.015.22', '76.00.016.23', '76.00.017.24', '76.00.018.25', '76.00.019.26', '76.00.020.27', '76.00.021.28', '76.00.022.29',
            '76.00.004.7', '76.00.005.8', '76.00.1064.1279', '76.00.1068.1283', '76.00.1069.1284', '76.00.1070.1285', '76.00.1071.1286', '76.00.1072.1287',
            '76.00.1073.1288', '76.00.1074.1289', '76.00.1075.1290', '76.00.1076.1291', '76.00.1077.1292', '76.00.1078.1293', '76.00.1079.1294', '76.00.1080.1295',
            '76.00.1081.1296', '76.00.1082.1297', '76.00.1083.1298', '76.00.1084.1299', '76.00.1085.1300', '76.00.1086.1301', '76.00.1087.1302', '76.00.1088.1303',
            '76.00.1089.1304', '76.00.1090.1305', '76.00.1091.1306', '76.00.1092.1307', '76.00.1093.1308', '76.00.1094.1309', '76.00.1095.1310', '76.00.1096.1311',
            '76.00.1097.1312', '76.00.1098.1313', '76.00.1099.1314', '76.00.1100.1315', '76.00.1101.1316', '76.00.1102.1317', '76.00.1103.1318', '76.00.1104.1319',
            '76.00.1105.1320', '76.00.945.1160', '76.00.981.1196', '76.00.982.1197'];

        $sql = "UPDATE rds.release_request SET obj_status_did=13 WHERE obj_status_did=4 AND NOT rr_build_version IN ('" . implode("', '", $allowedList) . "')";

        $this->execute($sql);
    }
}
