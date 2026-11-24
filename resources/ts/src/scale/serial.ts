export type UnitlessWeight = 'kg' | 'g';
type FrameStatus = 'stable' | 'unstable' | 'overload' | 'negative' | 'zero' | 'invalid';

export type ScaleFrame = {
  raw: string;
  status: FrameStatus;
  kg?: number;
  accepted?: boolean;
};

export function parseScaleLine(raw: string, unitless: UnitlessWeight): ScaleFrame {
  const text = raw.trim();
  if (/\b(?:OL|OVERLOAD)\b/i.test(text)) return { raw, status: 'overload' };
  if (/\bUS\b/i.test(text)) return { raw, status: 'unstable' };

  const match = text.match(/^(?:ST[,\s]+)?(?:GS[,\s]+)?([+-]?)\s*(\d+(?:[.,]\d+)?)\s*(kg|kgl|gm|gr|g)?$/i);
  if (!match?.[2]) return { raw, status: 'invalid' };

  const unit = (match[3] || unitless).toLowerCase();
  const value = Number(match[1] + match[2].replace(',', '.'));
  if (!Number.isFinite(value)) return { raw, status: 'invalid' };
  const kg = Math.round((unit.startsWith('kg') ? value : value / 1000) * 1000) / 1000;
  if (value < 0) return { raw, status: 'negative', kg };
  if (value === 0) return { raw, status: 'zero', kg };
  if (kg === 0) return { raw, status: 'invalid', kg };
  return { raw, status: 'stable', kg };
}

/** Frames are delimited by CR, LF or CRLF. A zero frame releases the accepted reading. */
export function createScaleStream(unitless: UnitlessWeight) {
  let buffer = '';
  let latched = false;
  let discarding = false;

  return (chunk: string): ScaleFrame[] => {
    const frames: ScaleFrame[] = [];
    for (const character of chunk) {
      if (character === '\r' || character === '\n') {
        if (buffer.trim() && !discarding) {
          const frame = parseScaleLine(buffer, unitless);
          if (frame.status === 'zero') latched = false;
          if (frame.status === 'stable' && !latched) {
            latched = true;
            frame.accepted = true;
          }
          frames.push(frame);
        }
        buffer = '';
        discarding = false;
      } else if (!discarding) {
        buffer += character;
        if (buffer.length > 256) {
          frames.push({ raw: buffer, status: 'invalid' });
          buffer = '';
          discarding = true;
        }
      }
    }
    return frames;
  };
}
