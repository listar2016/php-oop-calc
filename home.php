<?php
    class Product {
        var $sku = "";
        var $price = 0.0;
        var $cost = 0.0;
        var $qty = 0.0;
        
        function calcProfit() {
            return $this->price - $this->cost;
        }

    }
    class ProductList {

        public $products = array();

        function initProducts( $file_name) {
            $header_info = array();
            $row = 1;
            if (($handle = fopen($file_name, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $num = count($data);
                    if ($row == 1) {
                        for ($c=0; $c < $num; $c++) {
                            array_push($header_info,$data[$c]);
                        }
                    } else {
                        $cur_product = new Product();
                        for ($c=0; $c < $num; $c++) {
                            $var_name = $header_info[$c];
                            $cur_product->$var_name = $data[$c];
                        }
                        array_push($this->products, $cur_product);
                    }
                    $row++;
                }
                fclose($handle);
            }
        }

        function getExchangeRate() {

            $url = "https://api.exchangeratesapi.io/latest";
            $response = file_get_contents($url);
            $data = json_decode($response);

            return $data->rates->CAD;
        }

    }
   if(isset($_FILES['csvFile'])){
      $file_name = $_FILES['csvFile']['name'];
      $file_size =$_FILES['csvFile']['size'];
      $file_tmp =$_FILES['csvFile']['tmp_name'];
      $file_type=$_FILES['csvFile']['type'];
      $file_ext=strtolower(end(explode('.',$_FILES['csvFile']['name'])));

      $new_file_name = "csv/" . $file_name;
      move_uploaded_file($file_tmp, $new_file_name);
      $products = new ProductList();
      $products->initProducts($new_file_name);
    
   }
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .calc-body {
            width: 90%;
            margin: auto;
        }
        .file-upload {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        table {
            font-family: arial, sans-serif;
            border-collapse: collapse;

        }

        td, th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }

        td.red {
            color : #ff0000;
        }

        td.green {
            color: #00ff00;
        }

        tr:nth-child(even) {
            background-color: #dddddd;
        }
    </style>
</head>
<body>
    <div class="calc-body">
        <form class="file-upload" action="" method="post" enctype="multipart/form-data">
            Select CSV file to upload:
            <input type="file" name="csvFile" id="csvFile">
            <input type="submit" value="Upload File" name="submit">
        </form>
        <table>
            <tr>
                <th>&nbsp;</th>
                <th>SKU</th>
                <th>Cost</th>
                <th>Price</th>
                <th>QTY</th>
                <th>Profit Margin</th>
                <th>Total Profit (USD)</th>
                <th>Total Profit (CAD)</th>
            </tr>
            <?php
                if (isset($products)) {
                    $rate = $products->getExchangeRate();
                    $total_qty = 0.0;
                    $total_price = 0.0;
                    $total_profit = 0.0;
                    $total_all_profit = 0.0;
                    $cnt = 0;
                    foreach ($products->products as $cur_product) {

                        $cur_profit = $cur_product->calcProfit();
                        $cnt++;
                        if ($cur_profit >= 0) $cur_class = 'green'; else $cur_class = 'red';
                        echo "<tr>";
                        echo "<td>" . ++$cnt . "</td>";
                        echo "<td>" . $cur_product->sku . "</td>";
                        echo "<td>" . money_format('$%i', $cur_product->cost) . "</td>";
                        echo "<td>" . money_format('$%i', $cur_product->price) . "</td>";
                        echo "<td>" . $cur_product->qty . "</td>";
                        echo "<td class='$cur_class'>" . money_format('$%i', $cur_profit) . "</td>";
                        echo "<td class='$cur_class'>" . money_format('$%i', $cur_profit * $cur_product->qty) . "</td>";
                        echo "<td class='$cur_class'>" . money_format('$%i', $cur_profit * $cur_product->qty * $rate) . "</td>";
                        echo "</tr>";

                        $total_qty += $cur_product->qty;
                        $total_price += $cur_product->price * $cur_product->qty;
                        $total_profit += $cur_profit * $cur_product->qty;
                    }
                    if ($total_qty > 0) {
                        $avg_price = $total_price / $total_qty;
                        $avg_profit = $total_profit / $total_qty;
                    } else {
                        $avg_price = 0.0;
                        $avg_profit = 0.0;
                    }
                    echo "<tr>";
                    echo "<td colspan=3>Total</td>";
                    echo "<td>" . money_format('$%i', $avg_price) . "</td>";
                    echo "<td>" . $total_qty . "</td>";
                    echo "<td>" . money_format('$%i', $avg_profit) . "</td>";
                    echo "<td>" . money_format('$%i', $total_profit) . "</td>";
                    echo "<td>" . money_format('$%i', $total_profit * $rate) . "</td>";
                    echo "</tr>";
                }
            ?>
        </table>
    </div>
</body>
</html>