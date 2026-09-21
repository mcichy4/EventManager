import EventCard from "../components/events/EventCard";

const events = [
        {
      id: 1,
      title: "Warsztaty: pierwsze kroki z ceramiką",
      category: "Warsztaty",
      location: "Pracownia Forma, Warszawa",
      startsAt: "2026-10-11T10:00:00",
      color: "terracotta",
    },
    {
      id: 2,
      title: "Nocny bieg po nadwiślańskich bulwarach",
      category: "Sport",
      location: "Bulwary Wiślane, Warszawa",
      startsAt: "2026-10-18T19:00:00",
      color: "violet",
    },
    {
      id: 3,
      title: "Spotkanie dla ludzi z pomysłami",
      category: "Networking",
      location: "Centrum Kreatywne Targowa, Warszawa",
      startsAt: "2026-10-24T18:30:00",
      color: "yellow",
    },
];

export default function HomePage() {
    return (
        <>
            <section className="hero-section">
                <p className="eyebrow">Znajdź coś dla siebie</p>
                <h1>Wydarzenia, które warto przeżyć</h1>
                <p className="hero-section__description">
                    Odkrywaj warsztaty, koncerty i inne wydarzenia w Twojej okolicy. Zarezerwuj miejsce i dołącz do społeczności pasjonatów.
                </p>

                <form className="event-search">
                    <label htmlFor="event-search" className="sr-only">Szukaj wydarzenia</label>
                    <input
                        id="event-search"
                        type="search"
                        placeholder="Szukaj wydarzenia"
                        aria-label="Szukaj wydarzenia"
                        />
                        <button className="button button-primary" type="submit">Szukaj</button>
                </form>
            </section>

            <section className="events-section">
                <div className="section-heading">
                    <div>
                        <p className="eyebrow">Nadchodzące wydarzenia</p>
                        <h2>Odkryj wydarzenia w swojej okolicy</h2>
                    </div>
                    <button className="text-button" type="button">Wszystkie wydarzenia -&gt;</button>
                </div>

                <div className="event-grid">
                    {events.map((event) => (
                        <EventCard key={event.id} event={event} />
                    ))}
                </div>
            </section>
        </>
    );
}