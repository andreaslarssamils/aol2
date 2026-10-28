import { notFound } from 'next/navigation'

/** Fångar sökvägar med flera segment som ingen route äger, så att 404-sidan får sajtens layout. */
export default function CatchAllPage() {
  notFound()
}
