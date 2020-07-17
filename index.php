<?php

$json = file_get_contents("Code Challenge (DEV_Sales_full).json");
$data = json_decode($json);

if($json){

    $dsn = 'mysql:host=localhost;dbname=book_shop'; // Data srouce
    $user = 'root'; // DB user
    $pass = '';

    try{
        $db = new PDO($dsn, $user, $pass); // start DB connection
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $customer_sql = "INSERT INTO customers (name, mail) VALUES (?,?) ON DUPLICATE KEY UPDATE mail=?";
        $product_sql = "INSERT INTO products (id, name) VALUES (?,?) ON DUPLICATE KEY UPDATE id=?";
        $price_sql = "INSERT INTO product_versions (version, product_id, price) VALUES (?,?,?) ON DUPLICATE KEY UPDATE version=?";
        $sale_sql = "INSERT INTO sales (sales_id, date, customer_id, product_id, version_id) VALUES (?,?,(SELECT id FROM customers WHERE name = ?),?,(SELECT id FROM product_versions WHERE version = ?)) ON DUPLICATE KEY UPDATE sales_id=?";
        

        foreach ($data as $innerArray) {         
            $db->prepare($customer_sql)->execute([$innerArray->customer_name, $innerArray->customer_mail, $innerArray->customer_mail]);
            $db->prepare($product_sql)->execute([$innerArray->product_id, $innerArray->product_name ,$innerArray->product_id]);
            $db->prepare($price_sql)->execute([$innerArray->version, $innerArray->product_id ,$innerArray->product_price,$innerArray->version]);
            $db->prepare($sale_sql)->execute([$innerArray->sale_id, $innerArray->sale_date ,$innerArray->customer_name,$innerArray->product_id,$innerArray->version,$innerArray->sale_id]);
        }


        $get_sql = '
            SELECT sales.sales_id AS id,
            sales.date,
            customers.name,
            customers.mail,
            products.name AS product,
            product_versions.version,
            product_versions.price
            FROM sales
            join customers on sales.customer_id = customers.id
            join products on sales.product_id = products.id
            JOIN product_versions on sales.version_id = product_versions.id
        ';

        $results = $db->query($get_sql);
        $results->setFetchMode(PDO::FETCH_ASSOC);
    }
    catch(PDOException $e){
        echo 'failed :'. $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Sales page</title>
    </head>
    <body>
        <div id="app">
            <input size="50" type="text" placeholder="Filter by customer name, product name or price" v-model="filter" />
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Mail</th>
                        <th>Product</th>
                        <th>Version</th>
                        <th>Price</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in filteredRows">
                        <td v-html="highlightMatches(row.sale_id)"></td>
                        <td v-html="highlightMatches(row.customer_name)"></td>
                        <td v-html="highlightMatches(row.customer_mail)"></td>
                        <td v-html="highlightMatches(row.product_name)"></td>
                        <td v-html="highlightMatches(row.version)"></td>
                        <td v-html="highlightMatches(row.product_price)"></td>
                        <td v-html="convertToUTC(highlightMatches(row.sale_date),row.version)"></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr><td>Total Price</td><td v-html="sumFilteredRows"></td></tr>
                </tfoot>
            </table>
        </div>
    </body>
</html>



  
<!-- VueJS -->
<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
<script>
    const app = new Vue({
        el: '#app',
        data: {
        filter:'',
        rows: [],
        total: 0,
    },

    mounted() {
        var apiURL = "Code Challenge (DEV_Sales_full).json";
        fetch(apiURL)
        .then(resp => resp.json())
        .then(resp => (this.rows = resp))
        .catch(error => console.log(error));
    },

    methods: {
        highlightMatches(text) {
        const matchExists = text.toLowerCase().includes(this.filter.toLowerCase());
        if (!matchExists) return text;

        const re = new RegExp(this.filter, 'ig');
        return text.replace(re, matchedText => `<strong>${matchedText}</strong>`);
        },

        convertToUTC(currentDate,version){

            let strippedDate = new Date(currentDate.replace(/<[^>]+>/g, ''));
            let utcDate =  new Date(strippedDate+' GMT+2:00'); 
           console.log(utcDate);
            let convertedDate = version >= '1.0.17+60'? strippedDate : utcDate;
            return convertedDate;

        }
    },

    computed: {
        filteredRows() {

            this.total = 0;
            return this.rows.filter(row => {
                const customer = row.customer_name.toLowerCase();
                const product = row.product_name.toLowerCase();
                const price = row.product_price.toLowerCase();
                const searchTerm = this.filter.toLowerCase();

                return customer.includes(searchTerm) || product.includes(searchTerm) || price.includes(searchTerm);
            });
        },
        sumFilteredRows: function () {
            return this.filteredRows.reduce((a, b) => parseFloat(a) + parseFloat(b.product_price), 0)
        }
    },
});
</script>