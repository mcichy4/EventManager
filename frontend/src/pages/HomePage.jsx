import {useEffect, useState} from "react";
import EventCard from "../components/events/EventCard";
import {getEvents} from "../api/events";

export default function HomePage() {
    const [events, setEvents] = useState([]);
    const [search, setSearch] = useState("");
    const [submittedSearch, setSubmittedSearch] = useState("");
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const loadEvents = async () => {
            setIsLoading(true);
            setError(null);

            try {
                const response = await getEvents(submittedSearch);
                setEvents(response.data);
            } catch {
                setError("Nie udało się pobrać wydarzeń. Spróbuj ponownie za chwilę.");
            } finally {
                setIsLoading(false);
            }
        };

        loadEvents();
    }, [submittedSearch]);

    const handleSearch = (event) => {
        event.preventDefault();
        setSubmittedSearch(search.trim());
    };

    return (
        <>
            <section className="hero-section">
                <p className="eyebrow">Znajdź coś dla siebie</p>
                <h1>Wydarzenia, które warto przeżyć</h1>
                <p className="hero-section__description">
                    Odkrywaj warsztaty, koncerty i inne wydarzenia w Twojej okolicy. Zarezerwuj miejsce i dołącz do społeczności pasjonatów.
                </p>

                <form className="event-search" onSubmit={handleSearch}>
                    <label htmlFor="event-search" className="sr-only">Szukaj wydarzenia</label>
                    <input
                        id="event-search"
                        type="search"
                        placeholder="Szukaj wydarzenia"
                        aria-label="Szukaj wydarzenia"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
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
                    {isLoading && <p>Ładowanie wydarzeń...</p>}
                    {error && <p role="alert">{error}</p>}
                    {!isLoading && !error && events.length === 0 && (
                        <p>Nie znaleźliśmy wydarzeń spełniających te kryteria.</p>
                    )}
                    {!isLoading && !error && events.map((event) => (
                        <EventCard key={event.id} event={event} />
                    ))}
                </div>
            </section>
        </>
    );
}
