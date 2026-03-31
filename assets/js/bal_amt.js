const mrp = document.querySelector('#mrp');
const pamt = document.querySelector('#pamt');
const discount = document.querySelector('#discount');
const npaid = document.querySelector('#nxtpay');
const balAmt = document.querySelector('#balAmt');

mrp.addEventListener("input", balanceCal);
pamt.addEventListener("input", balanceCal);
discount.addEventListener("input", balanceCal);
npaid.addEventListener("input", balanceCal);

function balanceCal() {
    let mrpvalue = parseFloat(mrp.value) || 0;
    let pamtvalue = parseFloat(pamt.value) || 0;
    let discountval = parseFloat(discount.value) || 0;
    let npaidval = parseFloat(npaid.value) || 0;

    let balance = mrpvalue - pamtvalue - npaidval - discountval;
    balAmt.value = balance.toFixed(2);
    console.log(mrpvalue);
}

balanceCal();