(() => {
	const root = document.getElementById('chatbot-root');
	if (!root) return;
	root.insertAdjacentHTML('beforeend', `
		<button class="btn btn-primary chatbot-toggle">
			<span class="me-1">💬</span> Help
		</button>
		<div class="chatbot-panel" id="chatbotPanel" aria-live="polite">
			<div class="chatbot-header d-flex justify-content-between align-items-center">
				<strong>Clothyyy Assistant</strong>
				<button class="btn btn-sm btn-light" id="chatbotClose">✕</button>
			</div>
			<div class="chatbot-body" id="chatbotBody">
				<div class="chat-msg bot">Hi! I can help you navigate: try searching products, adding to cart, renting clothes, or scheduling a home try-on.</div>
			</div>
			<div class="chatbot-input">
				<input class="form-control" id="chatbotInput" placeholder="Type a question like 'How to rent?'">
				<button class="btn btn-primary" id="chatbotSend">Send</button>
			</div>
		</div>
	`);
	const toggle = root.querySelector('.chatbot-toggle');
	const panel = document.getElementById('chatbotPanel');
	const closeBtn = document.getElementById('chatbotClose');
	const sendBtn = document.getElementById('chatbotSend');
	const input = document.getElementById('chatbotInput');
	const body = document.getElementById('chatbotBody');

	const rules = [
		{ q: /rent|rental/i, a: "Go to Rent from navbar. Pay a refundable deposit during checkout. On return, if the item has defects, the deposit is adjusted up to the full product price." },
		{ q: /try[\s-]?on|home/i, a: "Use Try-On to schedule a home try. Return within 3 hours. Delivery charge applies (₹49)." },
		{ q: /pay|payment|upi|razor/i, a: "At checkout, pick UPI. A demo RazorPay-style modal opens. Use any UPI string to simulate paid status." },
		{ q: /order|track/i, a: "Open Orders from navbar to view your placed orders and their status." },
		{ q: /search|find/i, a: "Use the search bar on Home or Shop to find products by name or category." },
		{ q: /refund|deposit/i, a: "Rental deposit is refunded after verification. If defective, you pay up to full price." },
		{ q: /help|hi|hello/i, a: "I can guide you to Shop, Rent, Try-On, and Orders. What do you need?" }
	];

	function botSay(text) {
		const el = document.createElement('div');
		el.className = 'chat-msg bot';
		el.textContent = text;
		body.appendChild(el);
		body.scrollTop = body.scrollHeight;
	}
	function userSay(text) {
		const el = document.createElement('div');
		el.className = 'chat-msg user';
		el.textContent = text;
		body.appendChild(el);
		body.scrollTop = body.scrollHeight;
	}
	function reply(q) {
		for (const r of rules) {
			if (r.q.test(q)) return r.a;
		}
		return "Try: 'How to rent', 'Try-On rules', 'Payment', 'Orders', or 'Search'.";
	}

	toggle.addEventListener('click', () => panel.classList.toggle('active'));
	closeBtn.addEventListener('click', () => panel.classList.remove('active'));
	sendBtn.addEventListener('click', () => {
		const val = input.value.trim();
		if (!val) return;
		userSay(val);
		input.value = '';
		setTimeout(() => botSay(reply(val)), 300);
	});
	input.addEventListener('keydown', (e) => {
		if (e.key === 'Enter') sendBtn.click();
	});
})();








