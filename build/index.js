!(function () {
    "use strict";
    var t = window.React,
        e = window.wp.htmlEntities,
        n = window.wc.wcBlocksRegistry,
        i = window.wc.wcSettings;
    const o = () => {
        const t = (0, i.getSetting)("speed_payment_gateway_data");
        if (!t) throw new Error("Speed Payment Method initialization data is not available");
        return t;
    };
    const r = () => (0, e.decodeEntities)(o()?.description || "");
    const l = () => (0, e.decodeEntities)(o()?.title || "");
    (0, n.registerPaymentMethod)({
        name: "speed_payment_gateway",
        label: (0, t.createElement)(
		  () => (
		    (0, t.createElement)("div", null,
		    	(0, t.createElement)("label", null, o()?.title),
		    	o()?.logo_url && (0, t.createElement)("img", {
			        src: o()?.logo_url,
			        alt: o()?.title,
			        style: { 
			          width: '24px',
			          height: '24px',
			          top: '7px',
			          marginLeft: '5px',
			          position:'relative'
			        }
			      })
		    )
		  ),
		  null
		),
        ariaLabel: ((o()?.title || "")),
        canMakePayment: () => !0,
        content: (0, t.createElement)(r, null),
        edit: (0, t.createElement)(r, null),
    });
})();