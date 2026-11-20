const fs = require('fs');
const readline = require('readline');
const rl = readline.createInterface({
  input: fs.createReadStream('C:/Users/user/.gemini/antigravity/brain/54bfb974-bd67-493c-ad90-0ada8374c7bb/.system_generated/logs/transcript_full.jsonl'),
  crlfDelay: Infinity
});
rl.on('line', (line) => {
  if (line.includes('"step_index":7939')) {
    const data = JSON.parse(line);
    fs.writeFileSync('scratch/prompt7939.txt', data.content, 'utf8');
    console.log('Saved! Length:', data.content.length);
    process.exit(0);
  }
});
