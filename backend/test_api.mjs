async function test() {
    try {
        const res = await fetch('http://127.0.0.1:8000/api/debug/doctors/1', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        console.log("Status:", res.status);
        console.log(JSON.stringify(data, null, 2));
    } catch(e) {
        console.error(e);
    }
}
test();
