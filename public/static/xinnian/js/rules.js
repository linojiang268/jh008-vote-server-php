var RulesPanel = React.createClass({
    componentDidMount: function() {
        $('#app').scrollTop(0);
    },

    render: function() {
        return (
            <div>
                <img className="res-img" src="/static/xinnian/images/rules_1.jpg" />
                <div className="organization-w">
                    <h3>活动介绍</h3>
                    <p>活动时间：2016年1月22日（星期五）18:30-21:00</p>
                    <p>活动地点：武侯区文化馆10F（武侯区九兴大道619号）</p>
                    <p>活动规模：150人，男女比例接近1:1，报满即止</p>
                    <p>报名对象：武侯区单身青年</p>
                    <p>报名时间：即日起到1月19日</p>

                    <h3>报名方式</h3>
                    <p>1. 集体报名</p>
                    <p>请联系青春同路青年俱乐部索取集体报名表，填写完整后回传至邮箱：<a href="mailto:qctlmail@163.com">qctlmail@163.com</a>，咨询电话：<a href="tel://028-85126556">028-85126556</a>。</p>
                    <p>2. 个人报名</p>
                    <p>搜索微信公众号"青春武侯"，关注后点击【青年之声】-【青年交友】了解活动详情，点击【活动报名】-填写个人资料-提交资料-等待审核通过-报名页面显示。报名成功后即可拉票点赞，票数前10名即有机会活动iPad mini。</p>
                    <div style={{ textAlign: 'center', margin: '8px' }}>
                        <img src="/static/xinnian/images/qrcode.jpg" style={{ width: '160px', marginBottom: '6px' }} /><br />
                        <span style={{color: 'blue'}}>扫码关注 | 活动报名 | 告白抽奖</span>
                    </div>

                    <h3>活动咨询热线</h3>
                    <p><a href="tel://028-85126556">028-85126556</a></p>

                    <h3>活动出行方式（自行前往）</h3>
                    <p>需要在19:00前到达目的地进行签到，领取签到礼一份。</p>
                    <p>目的地：武侯区文化馆（武侯区九兴大道619号10楼）</p>

                    <h3>活动安排</h3>
                    <p>2016成都武侯区单身青年联谊会是服务武侯区内外青年的跨年度活动，由首场联谊活动暨开幕式与后续季度主题活动组成。</p>
                    <ul style={{ paddingLeft: '32px' }}>
                        <li>首场联谊会暨开幕式</li>
                    </ul>
                    <p style={{ marginLeft: '20px' }}>&nbsp;1）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>18:30-19:00</span>签到、领取ID</p>
                    <p style={{ marginLeft: '20px' }}>&nbsp;2）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>19:00-19:10</span>主持人开场</p>
                    <p style={{ marginLeft: '20px' }}>&nbsp;3）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>19:10-19:30</span>爱的行动</p>
                    <p style={{ marginLeft: '20px' }}>&nbsp;4）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>19:30-19:45</span>心动之约（第一轮）</p>
                    <p style={{ marginLeft: '20px' }}>&nbsp;5）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>19:45-19:55</span>三等奖抽奖</p>
                    <p style={{ marginLeft: '20px' }}>&nbsp;6）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>19:55-20:10</span>心动之约（第二轮）</p>
                    <p style={{ marginLeft: '20px' }}>&nbsp;7）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>20:10-20:20</span>二等奖抽奖</p>
                    <p style={{ marginLeft: '20px' }}>&nbsp;8）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>20:20-20:35</span>心动之约（第三轮）</p>
                    <p style={{ marginLeft: '20px' }}>&nbsp;9）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>20:35-20:45</span>真情表白/个人才艺秀</p>
                    <p style={{ marginLeft: '20px' }}>10）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>20:45-20:50</span>爱的选择/最佳人气王</p>
                    <p style={{ marginLeft: '20px' }}>11）<span style={{ paddingRight: '16px', fontWeight: 'bold' }}>20:50-21:00</span>一等奖抽奖/牵手成功奖</p>

                    <ul style={{ paddingLeft: '32px' }}>
                        <li>后续主题季度联谊活动</li>
                    </ul>
                    <p style={{ marginLeft: '20px' }}>
                        由微信公众号“青春武侯”发布，单身青年自愿参与。
                    </p>
                </div>
            </div>
        );
    }
});

module.exports = RulesPanel;