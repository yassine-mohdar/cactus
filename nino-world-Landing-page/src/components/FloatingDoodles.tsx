import { motion } from "framer-motion";

const doodles = [
  { emoji: "✨", size: "text-lg", top: "10%", left: "5%", delay: 0 },
  { emoji: "🌿", size: "text-xl", top: "20%", right: "8%", delay: 0.5 },
  { emoji: "⭐", size: "text-sm", top: "40%", left: "3%", delay: 1 },
  { emoji: "💚", size: "text-base", top: "60%", right: "5%", delay: 1.5 },
  { emoji: "🍃", size: "text-lg", top: "75%", left: "7%", delay: 0.8 },
  { emoji: "✨", size: "text-sm", top: "85%", right: "10%", delay: 1.2 },
  { emoji: "🌸", size: "text-base", top: "30%", left: "2%", delay: 0.3 },
  { emoji: "⭐", size: "text-lg", top: "50%", right: "3%", delay: 0.7 },
];

const FloatingDoodles = () => (
  <div className="fixed inset-0 pointer-events-none z-0 overflow-hidden">
    {doodles.map((d, i) => (
      <motion.span
        key={i}
        className={`absolute ${d.size} opacity-30`}
        style={{ top: d.top, left: d.left, right: d.right }}
        animate={{ y: [0, -10, 0], rotate: [0, 5, -5, 0] }}
        transition={{ duration: 4 + i * 0.5, repeat: Infinity, ease: "easeInOut", delay: d.delay }}
      >
        {d.emoji}
      </motion.span>
    ))}
  </div>
);

export default FloatingDoodles;
