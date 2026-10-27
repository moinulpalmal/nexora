import {
    Bullet,
    Bullets,
    SubHeading,
    Ui,
} from '@/components/training/vocabulary';

/**
 * The controls every list screen shares (ARCHITECTURE.md §8.6) — not a guide
 * of its own, a fragment the list guides include in their Fields section, so
 * the explanation is written once. `training:gaps` holds it to the same
 * English/Bangla pairing as a guide.
 */
export function ListBasics({ deletedTab = false }: { deletedTab?: boolean }) {
    return (
        <>
            <SubHeading
                en="Finding a record in the list"
                bn="তালিকায় রেকর্ড খোঁজা"
            />
            <Bullets>
                <Bullet
                    en="Each column has its own filter box under the heading. Type in it, or pick from it, and the list narrows as you go."
                    bn="প্রতিটি কলামের শিরোনামের নিচে আলাদা ফিল্টার ঘর আছে। সেখানে লিখুন বা বেছে নিন — সাথে সাথে তালিকা ছোট হয়ে আসে।"
                />
                <Bullet
                    en="Click a column heading to sort by it; click it again to reverse the order."
                    bn="কোনো কলামের শিরোনামে ক্লিক করলে সেই অনুযায়ী সাজানো হয়; আবার ক্লিক করলে উল্টো ক্রমে।"
                />
                <Bullet
                    en={
                        <>
                            <Ui>Rows</Ui> sets how many records a page shows;
                            the pager at the bottom moves between pages.
                        </>
                    }
                    bn={
                        <>
                            <Ui>Rows</Ui> দিয়ে ঠিক করুন এক পাতায় কতগুলো রেকর্ড
                            দেখাবে; নিচের পেজার দিয়ে পাতা বদলান।
                        </>
                    }
                />
                <Bullet
                    en={
                        <>
                            The refresh button reads the list again and keeps
                            your filters. <Ui>Clear filters</Ui> appears once
                            anything is filtered, and empties every filter at
                            once.
                        </>
                    }
                    bn={
                        <>
                            রিফ্রেশ বোতাম ফিল্টার ঠিক রেখে তালিকাটি আবার পড়ে।
                            কিছু ফিল্টার করা থাকলে <Ui>Clear filters</Ui> দেখা
                            যায়; এটি একবারে সব ফিল্টার মুছে দেয়।
                        </>
                    }
                />
                <Bullet
                    en="The list also reads itself again when you come back to the browser tab and when you open a dialog on it, so it picks up what others have changed in the meantime."
                    bn="ব্রাউজারের এই ট্যাবে ফিরে এলে এবং তালিকার কোনো ডায়ালগ খুললেও তালিকাটি নিজে থেকেই আবার পড়া হয়, তাই এর মধ্যে অন্যরা যা বদলেছেন তা-ও দেখা যায়।"
                />
                {deletedTab ? (
                    <Bullet
                        en={
                            <>
                                Deleted records move to the <Ui>Deleted</Ui>{' '}
                                tab. From there they can be restored, or deleted
                                for good — each needs its own permission.
                            </>
                        }
                        bn={
                            <>
                                মুছে ফেলা রেকর্ড <Ui>Deleted</Ui> ট্যাবে চলে
                                যায়। সেখান থেকে ফিরিয়ে আনা যায়, অথবা চিরতরে
                                মুছে ফেলা যায় — প্রতিটির জন্য আলাদা পারমিশন
                                লাগে।
                            </>
                        }
                    />
                ) : null}
            </Bullets>
        </>
    );
}
