<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- @vite('resources/css/app.css') -->
    <title>Laravel 10 Generate PDF From View</title>

    <style>
        .first-column {
            width: 50%;
            text-align: left;
        }

        .second-column {
            text-align: right;
        }

        .column {
            text-align: center;
        }

        .box-padding {
            padding: 12px;
        }

        .row-bottom {
            border-bottom: 1px solid #F3F3F3;
        }

        .liner {
            border: 1px solid red;
        }

        .product-title {
            display: inline-flex;
            border: 1px solid blue;
            width: 100%;
        }

        .product-image {
            width: 50px;
            height: 50px;
        }
    </style>
</head>

<body style=" width:100%; height:100%;">
    <div style="margin: 40px auto; width:100%; height:100%;">
        <table style="width: 96%; margin: auto;">
            <thead style="background-color: #F3F2FB; margin: auto;">
                @foreach ($columns as $key => $column)
                <th class="{{ $key === 0 ? 'first-column' : ($key === 1 ? 'second-column' : '') }} box-padding">{{ $column }}</th>
                @endforeach
            </thead>
            <tbody>
                @foreach ($products as $key => $product )
                <tr class="row-bottom">
                    <td class="first-column box-padding product-title">
                        <div class="product-image">
                            <img class="product-image" src="{{$product->thumbnail}}" alt="thumbnail">
                        </div>
                        <div>
                            {{$product->title}}
                        </div>
                    </td>
                    <td class="second-column box-padding">
                        {{$product->price}}
                    </td>
                    <td class="box-padding column">
                        30
                    </td>
                    <td class="box-padding column">
                        @switch($product->product_type)
                        @case('digital_product')
                        Digital Product
                        @break

                        @default
                        Digital Product
                        @endswitch
                    </td>

                    <td class="box-padding column">
                        {{$product->status}}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
