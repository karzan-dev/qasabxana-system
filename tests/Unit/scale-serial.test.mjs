import assert from 'node:assert/strict';
import test from 'node:test';
import { createScaleStream, parseScaleLine } from '../../resources/ts/src/scale/serial.ts';

test('explicit and configurable unitless weights are kilograms internally', () => {
  assert.equal(parseScaleLine('ST,GS,+  0.500 kg', 'kg').kg, 0.5);
  assert.equal(parseScaleLine('0250 g', 'kg').kg, 0.25);
  assert.equal(parseScaleLine('0.750', 'kg').kg, 0.75);
  assert.equal(parseScaleLine('750', 'g').kg, 0.75);
  assert.equal(parseScaleLine('5 g', 'kg').kg, 0.005);
  assert.equal(parseScaleLine('10 g', 'kg').kg, 0.01);
});

test('ignores unstable, overloaded, negative, and unknown frames', () => {
  const parse = createScaleStream('kg');
  const frames = parse('US,GS,+ 0.500 kg\rOL,GS,0.500 kg\nST,GS,- 0.002 kg\r\nnoise\r');
  assert.deepEqual(frames.map(frame => frame.status), ['unstable', 'overload', 'negative', 'invalid']);
  assert.equal(frames.some(frame => frame.accepted), false);
});

test('framing takes split chunks and accepts only one reading before zero', () => {
  const parse = createScaleStream('kg');
  assert.deepEqual(parse('ST,GS,+  0.'), []);
  assert.equal(parse('500 kg\r')[0].accepted, true);
  assert.equal(parse('\n0.550 kg\n')[0].accepted, undefined);
  assert.equal(parse('US,GS,0 kg\n0.600 kg\n')[1].accepted, undefined);
  assert.deepEqual(parse('0 g\r\n1.250 kg\n').map(({ status, accepted, kg }) => [status, accepted, kg]),
    [['zero', undefined, 0], ['stable', true, 1.25]]);
});
