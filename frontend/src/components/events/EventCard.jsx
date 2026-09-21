import {Link} from "react-router-dom";

export default function EventCard({event}) {
    const date = new Intl.DateTimeFormat("pl-PL", {
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(new Date(event.startsAt));

    return (
        <article className="event-card">
            <div className={`event-card__image event-card__image--${event.color}`}>
                <span>{event.category}</span>
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