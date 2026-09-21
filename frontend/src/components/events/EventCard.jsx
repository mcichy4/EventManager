import { Link } from "react-router-dom";

const categoryLabels = {
  workshops: "Warsztaty",
  sport: "Sport",
  music: "Muzyka",
  networking: "Networking",
  education: "Edukacja",
  other: "Inne",
};

const categoryColors = {
  workshops: "terracotta",
  sport: "violet",
  music: "yellow",
  networking: "violet",
  education: "terracotta",
  other: "yellow",
};

export default function EventCard({ event }) {
  const date = new Intl.DateTimeFormat("pl-PL", {
    day: "numeric",
    month: "long",
    year: "numeric",
  }).format(new Date(event.starts_at));

  const category = categoryLabels[event.category] ?? "Inne";
  const color = categoryColors[event.category] ?? "yellow";

  return (
    <article className="event-card">
      <div className={`event-card__image event-card__image--${color}`}>
        <span>{category}</span>
      </div>

      <div className="event-card__content">
        <p className="event-card__date">{date}</p>
        <h2>{event.title}</h2>
        <p className="event-card__location">{event.location}</p>

        <Link className="event-card__link" to={`/events/${event.id}`}>
          Zobacz wydarzenie<span aria-hidden="true"> →</span>
        </Link>
      </div>
    </article>
  );
}
