@extends('layouts.main')

@section('title', '社团二维码下载')
    <link rel="stylesheet" href="/static/css/team/assn.css"/>
@section('stylesheet')

@endsection

@section('content')
    <div class="qrcode-con">
        <h3 class="qc-title">二维码</h3>
        <div class="qc-box">
            <img src="{{ $qrCodeUrl }}" alt="">
        </div>
        <h3 class="qc-title">下载不同尺寸二维码</h3>
        <div class="qc-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>二维码边长（cm）</th>
                        <th>建议扫描距离（m）</th>                        
                        <th>下载链接</th>            
                    </tr>   
                </thead>
                <tbody>
                    <tr class="odd">
                        <td>8cm</td>
                        <td>0.5m</td>
                        <td><a href="qrcode/download?size=80"><i class="icon iconfont"></i></a></td>
                    </tr>
                    <tr class="even">
                        <td>12cm</td>
                        <td>0.8m</td>
                        <td><a href="qrcode/download?size=120"><i class="icon iconfont"></i></a></td>
                    </tr>
                    <tr class="odd">
                        <td>15cm</td>
                        <td>1m</td>
                        <td><a href="qrcode/download?size=150"><i class="icon iconfont"></i></a></td>
                    </tr>
                    <tr class="even">
                        <td>30cm</td>
                        <td>1.5m</td>
                        <td><a href="qrcode/download?size=300"><i class="icon iconfont"></i></a></td>
                    </tr>
                    <tr class="odd">
                        <td>50cm</td>
                        <td>2.5m</td>
                        <td><a href="qrcode/download?size=500"><i class="icon iconfont"></i></a></td>
                    </tr>                    
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('javascript')
    
@endsection