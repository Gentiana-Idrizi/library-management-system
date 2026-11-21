const PATHS = {
  book: 'M5 4.5A1.5 1.5 0 0 1 6.5 3H19v15H6.5A1.5 1.5 0 0 0 5 19.5m0-15v15m0 0A1.5 1.5 0 0 0 6.5 21H19v-3',
  bookmark: 'M7 3h10v18l-5-3.5L7 21z',
  history: 'M3 12a9 9 0 1 0 2.6-6.4M3 4v4h4m5-1v5l3 2',
  wallet: 'M4 7.5A2.5 2.5 0 0 1 6.5 5H18v3M4 7.5V17a2 2 0 0 0 2 2h14V8H6.5A2.5 2.5 0 0 1 4 7.5Zm12 6h.01',
  swap: 'M7 7h13l-3-3m3 13H4l3 3',
  shelf: 'M4 20h16M6 20V5h3v15m2 0V8h3v12m2.5 0-1.8-11 3-.5L19 20',
  users:
    'M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1m6.5-9a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM21 19v-1a4 4 0 0 0-3-3.9m-2-11a3.5 3.5 0 0 1 0 6.8',
  user: 'M18 20v-1a5 5 0 0 0-5-5h-2a5 5 0 0 0-5 5v1m6-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
  settings:
    'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7.6 7.6 0 0 0-2-1.2L14.5 3h-4l-.4 2.6a7.6 7.6 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7.4 7.4 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7.6 7.6 0 0 0 2 1.2l.4 2.6h4l.4-2.6a7.6 7.6 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2Z',
  list: 'M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01',
  bell: 'M12 3a6 6 0 0 0-6 6v3.6L4.3 16h15.4L18 12.6V9a6 6 0 0 0-6-6Zm-2 15a2 2 0 0 0 4 0',
  logout: 'M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 8l-4 4 4 4m-4-4h11',
  menu: 'M4 7h16M4 12h16M4 17h10',
  search: 'm20 20-4.2-4.2M10.5 17a6.5 6.5 0 1 0 0-13 6.5 6.5 0 0 0 0 13Z',
  plus: 'M12 5v14M5 12h14',
};

export default function Icon({ name, size = 20, className }) {
  return (
    <svg
      viewBox="0 0 24 24"
      width={size}
      height={size}
      fill="none"
      stroke="currentColor"
      strokeWidth="1.6"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
      aria-hidden="true"
    >
      <path d={PATHS[name]} />
    </svg>
  );
}
