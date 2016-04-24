<div class="ui-tab2">
    <ul class="ui-tab2-items">
        <li class="ui-tab2-item @if ($key === 'managerMember') ui-tab2-item-current @endif">
            <a href="/community/team/manager">已加入</a>
        </li>
        <li class="ui-tab2-item  @if ($key === 'verifyPend') ui-tab2-item-current @endif">
            <a href="/community/team/verify/pend">待审核</a>
        </li>
        <li class="ui-tab2-item  @if ($key === 'verifyRefuse') ui-tab2-item-current @endif">
            <a href="/community/team/verify/refuse">已拒绝</a>
        </li>
<!--         <li class="ui-tab2-item">
               <a href="/community/team/verify/blacklist">黑名单</a>
           </li>
           <li class="ui-tab2-item ui-tab2-item-current">
               <a href="/community/team/verify/whitelist">白名单</a>
           </li>    -->           
    </ul>
</div>