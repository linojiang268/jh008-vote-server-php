<style type="text/css">
    .tg  {border-collapse:collapse;border-spacing:0;border-color:#aabcfe;}
    .tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#aabcfe;color:#669;background-color:#e8edff;}
    .tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:#aabcfe;color:#039;background-color:#b9c9fe;}
    .tg .tg-0ord{text-align:right}
    .tg .tg-ifyx{background-color:#D2E4FC;text-align:right}
    .tg .tg-s6z2{text-align:center}
    .tg .tg-vn4c{background-color:#D2E4FC}
</style>
<table class="tg">
    <tr>
        <th class="tg-s6z2" colspan="7">已结束活动统计</th>
    </tr>
    <tr>
        <th class="tg-vn4c">活动名称</th>
        <th class="tg-vn4c">报名数</th>
        <th class="tg-vn4c">成功报名数</th>
        <th class="tg-vn4c">缴费金额</th>
        <th class="tg-vn4c">签到数</th>
        <th class="tg-vn4c">用户上传相片数</th>
        <th class="tg-vn4c">主办方上传相片数</th>
    </tr>
    @foreach ($activitiesData as $activityData)
        <tr>
            <td class="{{ $activityData['style'] }}">{{ $activityData['title'] }}</td>
            <td class="{{ $activityData['style'] }}">{{ $activityData['applicant'] }}</td>
            <td class="{{ $activityData['style'] }}">{{ $activityData['member'] }}</td>
            <td class="{{ $activityData['style'] }}">{{ $activityData['amount'] }}</td>
            <td class="{{ $activityData['style'] }}">{{ $activityData['checkIn'] }}</td>
            <td class="{{ $activityData['style'] }}">{{ $activityData['userAlbum'] }}</td>
            <td class="{{ $activityData['style'] }}">{{ $activityData['sponsorAlbum'] }}</td>
        </tr>
    @endforeach
</table>
